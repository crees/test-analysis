<?php
namespace TestAnalysis;

require "../bin/classes.php";

if (Config::is_staff($auth_user)) {
    if (isset($_GET['masquerade'])) {
        $auth_user = strtolower($_GET['masquerade']);
        $is_staff = TRUE;
    } else {
        echo "<html><head></head><body><h3>Need to masquerade as a student?  Please put in their username:</h3>";
        die ("<p><form method=\"get\"><input type=\"text\" name=\"masquerade\"><input type=\"submit\"></form></body></html>");
    }
}

$msq = isset($_GET['masquerade']) ? "?masquerade={$_GET['masquerade']}" : "";

$student = Student::retrieveByDetail(Student::USERNAME, $auth_user);

/* Look up kid's ID from email address */
if (!isset($student[0])) {
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
    $student_id = $qEmailAddress[0]['emailAddressOwner']['id'];;
    $student = Student::retrieveByDetail(Student::ID, $student_id);
    
    if (!isset($student[0])) {
        die("Your email address $emailAddress does not appear to match any student in this database.");
    }
    
    $student[0]->setUsername($auth_user);
    $student[0]->commit();
}

$student = $student[0];

if (isset($_GET['getpdf']) && !empty($_GET['test'])) {
    $pdf = new \Imagick();
    $pdf->setresolution(150, 150);
    $test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
    $scannedTest = ScannedTest::retrieveByDetails([ScannedTest::TEST_ID, ScannedTest::STUDENT_ID], [$test->getId(), $student->getId()])[0];
    foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $scannedTest->getId(), ScannedTestPage::PAGE_NUM) as $page) {
        $pdf->readimageblob($page->getImageData());
        $pdf->scaleimage(0, 1700);
        $pdf->setImageFormat('pdf');
    }
    
    header("Content-type:application/pdf");
    header("Content-Disposition:attachment;filename={$test->getName()}.pdf");
    if ($scannedTest->get(ScannedTest::DOWNLOADED) == 0 && $scannedTest->secondsRemaining() > 0) {
        $scannedTest->markAsDownloaded();
        $scannedTest->setTime(intdiv($scannedTest->secondsRemaining(), 60) + 10);
    }
    $scannedTest->startTimer();
    echo $pdf->getimagesblob();
    die();
}

if (isset($_GET['getimgs']) && !empty($_GET['test'])) {
    $test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
    $scannedTest = ScannedTest::retrieveByDetails([ScannedTest::TEST_ID, ScannedTest::STUDENT_ID], [$test->getId(), $student->getId()])[0];
    foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $scannedTest->getId(), ScannedTestPage::PAGE_NUM) as $page) {
        echo "<img style=\"width: 100%\" src=\"../async/getScannedImage.php?imghash={$page->get(ScannedTestPage::SHA)}\">";
    }
    die();
}

if (!empty($_FILES)) {
    foreach ($scannedTest = ScannedTest::retrieveByDetail(ScannedTest::STUDENT_ID, $student->getId()) as $st) {
        if (isset($_FILES["input-file-{$st->getId()}"])) {
            if ($st->secondsRemaining() < 0) {
                die("Oops, sorry, too late!  Please email the test to your teacher immediately with an explanation as to why it was late!");
            }
            $f = $_FILES["input-file-{$st->getId()}"];
            if ($f['size'] > 0) {
                $pages = [];
                switch (strtolower(substr($f['name'], -4, 4))) {
                    case ".pdf":
                        try {
                            if (defined('TestAnalysis\Config::windows_path_to_gs_exe')) {
                                shell_exec(Config::windows_path_to_gs_exe . " -sDEVICE=jpeg -sOutputFile={$f['tmp_name']}-page-%03d.jpg -r150x150 -f -dBATCH -dNOPAUSE -q {$f['tmp_name']}");
                                $pages = [];
                                foreach (glob("{$f['tmp_name']}-page-[0-9][0-9][0-9].jpg") as $page) {
                                    array_push($pages, file_get_contents($page));
                                    unlink($page);
                                }
                            } else {
                                $im = new \Imagick();
                                $im->setresolution(150, 150);
                                $im->readimage($f['tmp_name']);
                                for ($i = 0; $i < $im->getnumberimages(); $i++) {
                                    $im->setiteratorindex($i);
                                    $im->setimageformat('jpg');
                                    $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                                    array_push($pages, $im->getimageblob());
                                }
                                $im->destroy();
                            }
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
                            array_push($pages, $zip->getFromName($name));
                        }
                        break;
                    default:
                        die("Sorry, only pdfs or zips are accepted");
                        break;
                }
                if (count($pages) > 0) {
                    // Store the component-page mapping
                    $component = [];
                    $old_pageIds = [];
                    // Delete all the old ones first!
                    foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $st->getId()) as $p) {
                        $component[$p->get(ScannedTestPage::PAGE_NUM)] = $p->get(ScannedTestPage::TESTCOMPONENT_ID);
                        array_push($old_pageIds, $p->getId());
                    }
                    if (count($old_pageIds) != count($pages)) {
                        die("The provided test had " . count($old_pageIds) . " pages and your uploaded test has only " . count($pages) . ".  Please click \"Back\" and try uploading the complete test.");
                    }
                    foreach ($old_pageIds as $p) {
                        ScannedTestPage::delete($p);
                    }
                    $num = 0;
                    foreach ($pages as $p) {
                        $page = new ScannedTestPage([
                            ScannedTestPage::SCANNEDTEST_ID => $st->getId(),
                            ScannedTestPage::TESTCOMPONENT_ID => $component[$num],
                            ScannedTestPage::PAGE_NUM => $num,
                        ]);
                        $page->setImageData($p);
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
    
    $scannedTests = ScannedTest::retrieveByDetail(ScannedTest::STUDENT_ID, $student->getId(), ScannedTest::TEST_ID);
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
                    // Delete completed practice test
                    if ($st->get(ScannedTest::TEST_ID) == 0) {
                        foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $st->getId()) as $page) {
                            ScannedTestPage::delete($page->getId());
                        }
                        ScannedTest::delete($st->getId());
                        array_pop($tests_marked);
                        break;
                    }
                    array_push($tests_to_mark, array_pop($tests_marked));
                    break;
                }
            }
        }
    }
    
    echo "<div class=\"h3\">Hello {$student->getName()}.</div>";
    echo "<div>";
    echo "<a href=\"" . Config::student_instruction_video . "\" class=\"btn btn-danger\">Please click here for video instructions</a>";
    if (count(ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$student->getId(), 0])) > 0) {
        echo "<a href=\"make_test_test.php$msq\" class=\"btn btn-danger\">Please click here to delete your practice test</a>";
    } else {
        echo "<a href=\"make_test_test.php$msq\" class=\"btn btn-success\">Please click here to generate a practice test</a>";
    }
    echo "</div>";
    echo "<div class=\"h4\">You have these tests to complete:</div><ul class=\"list-group\">";
    echo '<form method="POST" enctype="multipart/form-data">';
    foreach ($tests_to_complete as $st) {
        $testId = $st->get(ScannedTest::TEST_ID);
        if ($testId == 0) {
            $test_name = "Practice test";
        } else {
            $test_name = Test::retrieveByDetail(Test::ID, $testId)[0]->getName();
        }
        $time_left = round($st->secondsRemaining() / 60, 0);
        echo "<li class=\"list-group-item\">";
        echo "<a href=\"?test={$st->get(ScannedTest::TEST_ID)}&masquerade={$auth_user}\">$test_name, {$time_left} minutes allowed</a>";
        if ($st->get(ScannedTest::STUDENT_UPLOAD_ALLOWED) == true) {
            echo "<br>Your teacher has allowed you to download a pdf of this test, so that you can print and scan it, and reupload it here.";
            echo "<br>You will gain 10 minutes extra as a grace period for printing and scanning, but you <b>must</b> upload it before the timer";
            echo "<br>expires or the option will disappear and you will have to explain to your teacher.";
            echo "<br>In case it wasn't clear, clicking <i>Download</i> starts the timer.";
            echo "<br>You <b>must</b> use either a scanner to generate the pdf in order, or use Adobe Scan, Google Drive or Onedrive to make a readable PDF.";
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
        $test = Test::retrieveByDetail(Test::ID, $testId)[0];
        $test_name = $test->getName();
        $test_total = 0;
        foreach (TestComponent::retrieveByDetail(TestComponent::TEST_ID, $testId) as $c) {
            $test_total += $c->get(TestComponent::TOTAL);
        }
        echo "<li class=\"list-group-item\">";
        echo "<a href=\"?test={$st->get(ScannedTest::TEST_ID)}&getimgs=yes&masquerade={$auth_user}\">$test_name</a>";
        $total = 0;
        foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $st->getId()) as $p) {
            $total += $p->get(ScannedTestPage::PAGE_SCORE);
        }
        if ($total > 0) {
            echo " ($total/$test_total)";
        }
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
            echo " and <a href=\"feedback_sheet.php?test={$st->get(ScannedTest::TEST_ID)}&subject={$st->get(ScannedTest::SUBJECT_ID)}&student={$student->getId()}&masquerade=$auth_user\">feedback sheet</a>";
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
    if ($testId == 0) {
        $st = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$student->getId(), 0])[0];
    } else {
        $test = Test::retrieveByDetail(Test::ID, $testId)[0];
        $st = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$student->getId(), $test->getId()])[0];
    }
} catch (\Error $e) {
    die("You appear to be trying to retrieve a nonexistent test.");
}
if ($st->get(ScannedTest::TS_UNLOCKED) > time()) {
    die("The test is not unlocked yet!");
}

$testPage = ScannedTestPage::retrieveByDetails([ScannedTestPage::SCANNEDTEST_ID, ScannedTestPage::PAGE_NUM], [$st->getId(), $page_num]);

if (!isset($testPage[0])) {
    echo "<h3>Test complete, and saved!  You can now close the window.</h3>";
    
    die ("<a class=\"btn btn-warning\" href=\"?page=" . ($page_num - 1) . "&test=$testId&masquerade=$auth_user\">Previous page</a>");
}

$testPage = $testPage[0];

echo "<h3>";
if (isset($is_staff) && $is_staff) {
    echo "Not starting the timer as you are staff masquerading as $auth_user.";
} else {
    // Not starting the timer for staff!
    $st->startTimer();
    echo "<i class=\"fa fa-clock\"></i>";
    if (($minutes = round($st->secondsRemaining() / 60, 0)) <= 0) {
        die(" Sorry, out of time!");
    } else {
        $end = date('H:i', $st->get(ScannedTest::TS_STARTED) + $st->get(ScannedTest::MINUTES_ALLOWED) * 60);
        if ($testId == 0) {
            $testName = "Practice test";
        } else {
            $testName = $test->getName();
        }
        $pagecount = count(ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $st->getId()));
        $p = $page_num + 1;
        echo " $minutes minutes remaining on {$testName}- finishing at $end.  You are on page $p of $pagecount.";
    }
}
echo "</h3>";

echo "<div class=\"savebar\"></div>";

echo "<div class=\"row\">";

echo "<div class=\"col-lg-9\" id=\"testpage-container\">";
echo "<div class=\"prevPage\" onclick=\"togglePrevPage();\" hidden></div>";
echo "<div class=\"toolbar-container\" style=\"position: sticky; top: 0px; z-index: 100;\"></div><div id=\"testpage\"></div><div class=\"savebar\"></div></div>";
echo "<div class=\"col-lg-3\">";
if ($page_num > 0) {
    echo "<button class=\"btn btn-success\" onclick=\"togglePrevPage();\">Click to show previous page</button>";
}

echo "<dl class=\"row\">";
$defs = [
    ["Tool", "<span class=\"font-weight-bold\">Use</span>"],
    ["<i class=\"fa fa-ban\"></i>", "Disable tools- use this on a tablet when you want to move without changing anything"],
    ["<i class=\"fa fa-check\"></i>", "Use this for multiple-choice answers"],
    ["<i class=\"fas fa-times\"></i>", "Use this for points on graphs"],
    ["<i class=\"fas fa-square\"></i>", "Eraser tool- use to erase parts."],
    ["<i class=\"fa fa-circle-o\"></i>", "Draw an ellipse/circle"],
    ["<i class=\"fa fa-font\"></i>", "Type text"],
    ["<i class=\"fa fa-arrow-up\"></i>", "Draw a long arrow (use this for straight lines on too)"],
    ["<i class=\"fa fa-paint-brush\"></i>", "Freehand tool"],
];
foreach ($defs as $def) {
    echo "<dt class=\"col-2 text-lg-right\">{$def[0]}</dt>";
    echo "<dd class=\"col-10\">{$def[1]}</dd>";
}
echo "</dl>";

echo "</div>"; // col-md-2

echo "</div><div id=\"errors\"></div>";

?>

<script>
	cWidth = $('#testpage-container')[0].clientWidth;
	if (cWidth < 350) {
		cWidth = 350;
	}
    options = {
    		  width: cWidth - 30,
    		  height: (cWidth - 30) * 1.414,
    		  color: "blue",           // Color for shape and text
    		  type : "text",    // default shape: can be "rectangle", "arrow" or "text"
			  tools: ['undo', 'unselect', 'tick', 'cross', 'rectangle-filled', 'circle', 'text', 'arrow', 'pen', 'redo'], // Tools
    		  images: ["../async/getScannedImage.php?stpid=<?= $testPage->getId() ?>"],          // Array of images path : ["images/image1.png", "images/image2.png"]
    		  linewidth: 2,           // Line width for rectangle and arrow shapes
    		  fontsize: cWidth * 1.414 * 0.033 / 2 + "px",       // font size for text
			  lineheight: cWidth * 1.414 * 0.033 / 2,
    		  bootstrap: true,       // Bootstrap theme design
    		  position: "top-inside",       // Position of toolbar (available only with bootstrap)
			  toolbarContainer: '.toolbar-container',
    		  selectEvent: "change", // listened event on .annotate-image-select selector to select active images
    		  unselectTool: false,   // Add a unselect tool button in toolbar (useful in mobile to enable zoom/scroll)
			  imageExport: { type: "image/jpg", quality: 1 },
			  onAnnotate: function() {
				  for (elem of $('.savebar')) {
				  	elem.innerHTML = savebutton + dontsavebutton;
				  }
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
	  for (elem of $('.savebar')) {
		  	elem.innerHTML = prevbutton + nextbutton;
	  }
	});

	function dontsave() {
		for (elem of $('.savebar')) {
		  	elem.innerHTML = savebutton + prevbutton + nextbutton;
	  	}
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
		for (elem of $('.savebar')) {
		  	elem.innerHTML = "Saved! " + prevbutton + nextbutton;
	  	}
	}

	<?php
	
	if ($page_num > 0) {
	    $previous_page_id = ScannedTestPage::retrieveByDetails([ScannedTestPage::SCANNEDTEST_ID, ScannedTestPage::PAGE_NUM], [$st->getId(), $page_num - 1])[0]->getId();
	} else {
	    $previous_page_id = -1; 
	}
	
	?>
	
	function togglePrevPage() {
		previous_page_id = <?= $previous_page_id; ?>;
		if (previous_page_id == -1) {
			return;
		}
		for (elem of $('div.prevPage')) {
			if (elem.hidden) {
    			if (elem.innerHTML == '') {
    				elem.innerHTML = "<img src=../async/getScannedImage.php?stpid=" + previous_page_id + " style=\"max-width: 100%\">";
    			}
    			elem.hidden = false;
			} else {
				elem.hidden = true;
			}
		}
	}
</script>
</body>
</html>