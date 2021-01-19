<?php
namespace TestAnalysis;

require "../bin/classes.php";

if (Config::is_staff($auth_user)) {
    if (isset($_GET['masquerade'])) {
        $auth_user = $_GET['masquerade'];
        $_SESSION['is_staff'] = TRUE;
    } else {
        session_destroy();
        echo "<html><head></head><body><h3>Need to masquerade as a student?  Please put in their username:</h3>";
        die ("<p><form method=\"get\"><input type=\"text\" name=\"masquerade\"><input type=\"submit\"></form></body></html>");
    }
}

/* Look up kid's ID from email address */
if (!isset($_SESSION['student_id'])) {
    $emailAddress = $auth_user . "@" . Config::site_emaildomain;
    $emailQuery = "{ EmailAddress (emailAddress: \"$emailAddress\") { emailAddressOwner { id }}}";
    $client = new GraphQLClient();
    try {
        $qEmailAddress = $client->rawQuery($emailQuery)->getData()['EmailAddress'];
    } catch (\Exception $e) {
        die("<h3>Sorry, Arbor has not responded to finding out who you are.  Please try refreshing.</h3>");
    }
    Config::debug("Student::__construct: query complete");
    if (!isset($qEmailAddress[0])) {
        die("Your email address $emailAddress appears unrecognised.");
    }
    if (isset($qEmailAddress[1])) {
        die("Your email address appears to have more than one owner.  This cannot possibly be right");
    }
    if ($qEmailAddress[0]['emailAddressOwner']['entityType'] != 'Student') {
        die("Your email address $emailAddress appears not to belong to a student.");
    }
    $_SESSION['student_id'] = $qEmailAddress[0]['emailAddressOwner']['id'];
}

$student_id = $_SESSION['student_id'];
$student = Student::retrieveByDetail(Student::ID, $student_id);

if (!isset($student[0])) {
    die("Your email address $emailAddress does not appear to match any student in this database.");
}

$student = $student[0];

if (isset($_GET['getpdf']) && !empty($_GET['test'])) {
    $pdf = new \Imagick();
    $pdf->setresolution(150, 150);
    $test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
    $scannedTest = ScannedTest::retrieveByDetails([ScannedTest::TEST_ID, ScannedTest::STUDENT_ID], [$test->getId(), $student_id])[0];
    foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $scannedTest->getId(), ScannedTestPage::PAGE_NUM) as $page) {
        $pdf->readimageblob($page->get(ScannedTestPage::IMAGEDATA));
        $pdf->scaleimage(0, 1700);
        $pdf->setImageFormat('pdf');
    }
    
    header("Content-type:application/pdf");
    header("Content-Disposition:attachment;filename={$test->getName()}.pdf");
    echo $pdf->getimagesblob();
    die();
}

if (!empty($_FILES)) {
    foreach ($scannedTest = ScannedTest::retrieveByDetail(ScannedTest::STUDENT_ID, $student_id) as $st) {
        if (isset($_FILES["input-file-{$st->getId()}"])) {
            $f = $_FILES["input-file-{$st->getId()}"];
            if ($f['size'] > 0) {
                $pages = [];
                switch (substr($f['name'], -4, 4)) {
                    case ".pdf":
                        try {
                            $im = new \Imagick();
                            $im->setresolution(150, 150);
                            $im->readimage($f['tmp_name']);
                            for ($i = 0; $i < $im->getnumberimages(); $i++) {
                                $im->setiteratorindex($i);
                                $im->setimageformat('jpg');
                                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                                array_push($pages, addslashes($im->getimageblob()));
                            }
                            $im->destroy();
                        } catch (\ImagickException $e) {
                            die('Well, that\'s a shame.  For some reason, we can\'t extract pdf files, so please use <a href="https://www.ilovepdf.com/pdf_to_jpg">a pdf converter</a> to turn your pdf into a zipfile of images and try uploading that.');
                        }
                        break;
                    case ".zip":
                        /* Deal with zipped images in alphabetical order.  Very primitive :( */
                        $zip = new \ZipArchive();
                        $zip->open($f['tmp_name']);
                        $zipcontents = [];
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $n = $zip->getNameIndex($i);
                            if (preg_match('/[.]jpe?g$/', $n) == 1) {
                                array_push($zipcontents, $n);
                            }
                        }
                        sort($zipcontents);
                        foreach ($zipcontents as $name) {
                            array_push($pages, addslashes($zip->getFromName($name)));
                        }
                        break;
                    default:
                        die("Sorry, only pdfs or zips are accepted");
                        break;
                }
                if (count($pages) > 0) {
                    // Delete all the old ones first!
                    foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $st->getId()) as $p) {
                        ScannedTestPage::delete($p->getId());
                    }
                    $num = 0;
                    foreach ($pages as $p) {
                        $page = new ScannedTestPage([
                            ScannedTestPage::SCANNEDTEST_ID => $st->getId(),
                            ScannedTestPage::TESTCOMPONENT_ID => null,
                            ScannedTestPage::PAGE_NUM => $num,
                            ScannedTestPage::IMAGEDATA => $p,
                        ]);
                        $page->commit();
                        $num++;
                    }
                    $st->setTime(0);
                    $st->startTimer();
                }
            }
        }
    }
}

?>
<!doctype html>
<html>

<head>
<?php require "../bin/head.php"; ?>
</head>

<body>
	<div class="container">
		<br />
		<div class="h3"><a href="index.php"><img src="<?= Config::site_url ?>/img/<?= Config::site_small_logo ?>" style="width: 30%;" /></a></div>
<?php
if (!isset($_GET['test'])) {
    $tests_to_complete = [];
    $tests_to_mark = [];
    $tests_marked = [];
    
    $scannedTests = ScannedTest::retrieveByDetail(ScannedTest::STUDENT_ID, $student_id);
    foreach ($scannedTests as $st) {
        $time_left = round($st->secondsRemaining() / 60, 0);
        if ($time_left > 0) {
            if ($st->get(ScannedTest::TS_UNLOCKED) < time()) {
                array_push($tests_to_complete, $st);
            }
        } else {
            // Check the scores:
            array_push($tests_marked, $st);
            foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $st->getId(), "", '`' . ScannedTestPage::PAGE_SCORE . '`') as $p) {
                if (is_null($p->get(ScannedTestPage::PAGE_SCORE))) {
                    array_push($tests_to_mark, array_pop($tests_marked));
                    break;
                }
            }
        }
    }
    
    echo "<div class=\"h3\">Hello {$student->getName()}.</div>";
    echo "<div><a href=\"https://youtu.be/Hm42t_5_ijs\" class=\"btn btn-danger\">Please click here for video instructions</a></div>";
    echo "<div class=\"h4\">You have these tests to complete:</div><ul class=\"list-group\">";
    echo '<form method="POST" enctype="multipart/form-data">';
    foreach ($tests_to_complete as $st) {
        $testId = $st->get(ScannedTest::TEST_ID);
        $test_name = Test::retrieveByDetail(Test::ID, $testId)[0]->getName();
        echo "<li class=\"list-group-item\">";
        echo "<a href=\"?test={$st->get(ScannedTest::TEST_ID)}&masquerade={$auth_user}\">$test_name, {$time_left} minutes allowed</a>";
        if ($st->get(ScannedTest::STUDENT_UPLOAD_ALLOWED) == true) {
            echo "<br><a class=\"btn btn-primary\" href=\"?test={$st->get(ScannedTest::TEST_ID)}&masquerade={$auth_user}&getpdf=yes\">Download to complete on paper</a>";
            echo "<br><label class=\"form-label\" for=\"input-file-{$st->getId()}\">Scanned test to upload (jpgs in zip or pdf)</label>";
            echo "<input type=\"file\" class=\"form-control-file\" name=\"input-file-{$st->getId()}\" id=\"input-file-{$st->getId()}\">";
            echo " <button type=\"submit\" class=\"btn btn-primary\">Submit</button>";
        }
        echo "</li>";
    }
    echo '</form></ul>';
    echo '<div class="h4">Marked tests to review:</div><ul class="list-group">';
    foreach ($tests_marked as $st) {
        $testId = $st->get(ScannedTest::TEST_ID);
        $test_name = Test::retrieveByDetail(Test::ID, $testId)[0]->getName();
        echo "<li class=\"list-group-item\">";
        echo "<a href=\"?test={$st->get(ScannedTest::TEST_ID)}&getpdf=yes&masquerade={$auth_user}\">$test_name</a>";
        $test = Test::retrieveByDetail(Test::ID, $st->get(ScannedTest::TEST_ID))[0];
        $feedback_able = true;
        if (empty($test->get(Test::TARGETS)[0])) {
            $feedback_able = false;
        } else foreach ($test->getTestComponents() as $c) {
            $r = TestComponentResult::retrieveByDetails(
                [TestComponentResult::STUDENT_ID, TestComponentResult::TESTCOMPONENT_ID],
                [$student->getId(), $c->getId()],
                TestComponentResult::RECORDED_TS . ' DESC'
                );
            if (empty($r)) {
                $feedback_able = false;
                break;
            }
        }
        if ($feedback_able) {
            echo " (and <a href=\"feedback_sheet.php?test={$st->get(ScannedTest::TEST_ID)}&subject={$st->get(ScannedTest::SUBJECT_ID)}&student=$student_id&masquerade=$auth_user\">feedback sheet</a>)";
        }
        echo "</li>";
    }
    echo '</ul>';
    echo '<div class="h4">Tests awaiting marking:</div><ul class="list-group">';
    foreach ($tests_to_mark as $st) {
        $testId = $st->get(ScannedTest::TEST_ID);
        $test_name = Test::retrieveByDetail(Test::ID, $testId)[0]->getName();
        echo "<li class=\"list-group-item\">";
        echo "$test_name";
        echo "</li>";
    }
    echo '</ul>';
    die('</div></body></html>');
}

$page_num = $_GET['page'] ?? 0;
$testId = $_GET['test'];

try {
    $test = Test::retrieveByDetail(Test::ID, $testId)[0];
    $st = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$student_id, $test->getId()])[0];
    $st->getId();
} catch (\Error $e) {
    die("You appear to be trying to retrieve a nonexistent test.");
}
if ($st->get(ScannedTest::TS_UNLOCKED) > time()) {
    die("The test is not unlocked yet!");
}

$testPage = ScannedTestPage::retrieveByDetails([ScannedTestPage::SCANNEDTEST_ID, ScannedTestPage::PAGE_NUM], [$st->getId(), $page_num]);

if (!isset($testPage[0])) {
    echo "<h3>Test complete!</h3>";
    
    die ("<a class=\"btn btn-warning\" href=\"?page=" . ($page_num - 1) . "&test=$testId&masquerade=$auth_user\">Previous page</a>");
}

$testPage = $testPage[0];

echo "<h3>";
if (isset($_SESSION['is_staff']) && $_SESSION['is_staff']) {
    echo "Not starting the timer as you are staff masquerading as $auth_user.";
} else {
    // Not starting the timer for staff!
    $st->startTimer();
    echo "<i class=\"fa fa-clock\"></i>";
    if (($minutes = round($st->secondsRemaining() / 60, 0)) <= 0) {
        die(" Sorry, out of time!");
    } else {
        $end = date('H:i', $st->get(ScannedTest::TS_STARTED) + $st->get(ScannedTest::MINUTES_ALLOWED) * 60);
        echo " $minutes minutes remaining on {$test->getName()}- finishing at $end.";
    }
}
echo "</h3>";

echo "<div id=\"savebar\"></div>";

echo "<br /><br /><div id=\"testpage\"></div>";

?>

<script>
    options = {
    		  width: $('.container')[0].clientWidth,
    		  height: $('.container')[0].clientWidth * 1.414,
    		  color: "blue",           // Color for shape and text
    		  type : "text",    // default shape: can be "rectangle", "arrow" or "text"
			  tools: ['undo', 'unselect', 'tick', 'rectangle-filled', 'circle', 'text', 'arrow', 'pen', 'redo'], // Tools
    		  images: ["../async/getScannedImage.php?stpid=<?= $testPage->getId() ?>"],          // Array of images path : ["images/image1.png", "images/image2.png"]
    		  linewidth: 2,           // Line width for rectangle and arrow shapes
    		  fontsize: $('.container')[0].clientWidth * 1.414 * 0.033 / 2 + "px",       // font size for text
			  lineheight: $('.container')[0].clientWidth * 1.414 * 0.033 / 2,
    		  bootstrap: true,       // Bootstrap theme design
    		  position: "top",       // Position of toolbar (available only with bootstrap)
    		  selectEvent: "change", // listened event on .annotate-image-select selector to select active images
    		  unselectTool: false,   // Add a unselect tool button in toolbar (useful in mobile to enable zoom/scroll)
			  imageExport: { type: "image/jpg", quality: 1 },
			  onAnnotate: function() {
				  document.getElementById('savebar').innerHTML = savebutton + dontsavebutton;
			  },
    };
    savebutton = '<a class="btn btn-success" onclick="save()">Save this page</a>';
    dontsavebutton = '<a class="btn btn-danger" onclick="dontsave()">Do not save</a>';
	currentPage = <?= $page_num ?>;
    if (currentPage == 0) {
    	prevbutton = '';
    } else {
        prevbutton = '<a class="btn btn-warning" href="?page=' + (currentPage-1) + '&test=<?= $testId ?>&masquerade=<?= $auth_user ?>">Previous page</a>';
    }
    nextbutton = '<a class="btn btn-primary" href="?page=<?= ($page_num + 1) ?>&test=<?= $testId ?>&masquerade=<?= $auth_user ?>">Next page</a>';

	$(document).ready(function(){
	  $('#testpage').annotate(options);
	  document.getElementById('savebar').innerHTML = prevbutton + nextbutton;
	});

	function dontsave() {
		document.getElementById('savebar').innerHTML = savebutton + prevbutton + nextbutton;
	}
	
	function save() {
		$('#testpage').annotate('export', options, function(image) {
			  var xhr = new XMLHttpRequest();
			  xhr.open("POST", '../async/submitimg.php', true);

			  //Send the proper header information along with the request
			  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			  xhr.onreadystatechange = function() {
				    if (this.readyState == 4 && this.status == 200) {
				      saved();
				    }
				};
			  xhr.send("img=" + image + "&stpid=<?= $testPage->getId() ?>");
		  });
	}

	function saved() {
		document.getElementById('savebar').innerHTML = "Saved! " + prevbutton + nextbutton;
	}
	
</script>
</body>
</html>