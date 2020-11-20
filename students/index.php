<?php
namespace TestAnalysis;

require "../bin/classes.php";

/* TODO Look up kid's ID from email address */
$student_id = 566;

if (isset($_GET['getpdf']) && !empty($_GET['test'])) {
    $pdf = new \Imagick();
    $pdf->setresolution(150, 150);
    $test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
    $scannedTest = ScannedTest::retrieveByDetails([ScannedTest::TEST_ID, ScannedTest::STUDENT_ID], [$test->getId(), $student_id])[0];
    foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $scannedTest->getId(), ScannedTestPage::PAGE_NUM) as $page) {
        $pdf->readimageblob($page->get(ScannedTestPage::IMAGEDATA));
        $pdf->scaleimage(0, 842);
        $pdf->setImageFormat('pdf');
    }
    foreach ($pdf as $page) {
    }
    
    header("Content-type:application/pdf");
    header("Content-Disposition:attachment;filename={$test->getName()}.pdf");
    echo $pdf->getimagesblob();
    die();
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
		<div class="h3"><a href="index.php"><img src="<?= Config::site_url ?>/img/dshs.jpg" style="width: 30%;" /></a></div>
<?php
if (!isset($_GET['test'])) {
    $tests_to_complete = [];
    $tests_to_mark = [];
    $tests_marked = [];
    
    $scannedTests = ScannedTest::retrieveByDetail(ScannedTest::STUDENT_ID, $student_id);
    foreach ($scannedTests as $st) {
        $time_left = round($st->secondsRemaining() / 60, 0);
        if ($time_left > 0) {
            array_push($tests_to_complete, $st);
        } else {
            // Check the scores:
            array_push($tests_marked, $st);
            foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $st->getId()) as $p) {
                if (is_null($p->get(ScannedTestPage::PAGE_SCORE))) {
                    array_push($tests_to_mark, array_pop($tests_marked));
                    break;
                }
            }
        }
    }
    
    echo '<div class="h4">Tests to complete:</div><ul class="list-group">';
    foreach ($tests_to_complete as $st) {
        $testId = $st->get(ScannedTest::TEST_ID);
        $test_name = Test::retrieveByDetail(Test::ID, $testId)[0]->getName();
        echo "<li class=\"list-group-item\">";
        echo "<a href=\"?test={$st->get(ScannedTest::TEST_ID)}\">$test_name, {$time_left} minutes allowed</a>";
        echo "</li>";
    }
    echo '</ul>';
    echo '<div class="h4">Marked tests to review:</div><ul class="list-group">';
    foreach ($tests_marked as $st) {
        $testId = $st->get(ScannedTest::TEST_ID);
        $test_name = Test::retrieveByDetail(Test::ID, $testId)[0]->getName();
        echo "<li class=\"list-group-item\">";
        echo "<a href=\"?test={$st->get(ScannedTest::TEST_ID)}&getpdf=yes\">$test_name</a>";
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
    die();
}

$page_num = $_GET['page'] ?? 0;
$testId = $_GET['test'];

$test = Test::retrieveByDetail(Test::ID, $testId)[0];
$st = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$student_id, $test->getId()])[0];

$testPage = ScannedTestPage::retrieveByDetails([ScannedTestPage::SCANNEDTEST_ID, ScannedTestPage::PAGE_NUM], [$st->getId(), $page_num]);
if (!isset($testPage[0])) {
    die("<h3>Test complete!</h3>");
}

$testPage = $testPage[0];
$st->startTimer();

echo "<h3><i class=\"fa fa-clock\"></i>";

if (($minutes = round($st->secondsRemaining() / 60, 0)) <= 0) {
    die(" Sorry, out of time!");
} else {
    $end = date('H:i', $st->get(ScannedTest::TS_STARTED) + $st->get(ScannedTest::MINUTES_ALLOWED) * 60);
    echo " $minutes minutes remaining on {$test->getName()}- finishing at $end.</h3>";
}

echo "<div id=\"savebar\"></div>";

echo "<br /><br /><div id=\"testpage\"></div>";

?>

<script>
    options = {
    		  width: $('.container')[0].clientWidth,
    		  height: $('.container')[0].clientWidth * 1.414,
    		  color: "blue",           // Color for shape and text
    		  type : "text",    // default shape: can be "rectangle", "arrow" or "text"
			  tools: ['undo', 'unselect', 'rectangle-filled', 'circle', 'text', 'arrow', 'pen', 'redo'], // Tools
    		  images: ["data:image/jpg;base64,<?= base64_encode($testPage->get(ScannedTestPage::IMAGEDATA))?>"],          // Array of images path : ["images/image1.png", "images/image2.png"]
    		  linewidth: 2,           // Line width for rectangle and arrow shapes
    		  fontsize: $('.container')[0].clientWidth * 1.414 * 0.033 + "px",       // font size for text
			  lineheight: $('.container')[0].clientWidth * 1.414 * 0.033,
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
        prevbutton = '<a class="btn btn-warning" href="?page=' + (currentPage-1) + '&test=<?= $testId ?>">Previous page</a>';
    }
    nextbutton = '<a class="btn btn-primary" href="?page=<?= ($page_num + 1) ?>&test=<?= $testId ?>">Next page</a>';

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
			  xhr.open("POST", 'submitimg.php', true);

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