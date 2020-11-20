<?php
namespace TestAnalysis;

require "bin/classes.php";
require "dev/upgrade_database.php";

$allSubjects = Subject::retrieveAll(Subject::NAME);

if (isset($_GET['subject']) && !empty($_GET['subject'])) {
    $subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
    $teachingGroups = $subject->getTeachingGroups();
    $tests = $subject->getTests();
    
    if (isset($_GET['teaching_group']) && !empty($_GET['teaching_group'])) {
        $teaching_group = $_GET['teaching_group'];
        $students = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $teaching_group)[0]->getStudents();
        if (isset($_GET['test']) && !empty($_GET['test'])) {
            $test = Test::retrieveByDetail(Test::ID, $_GET['test']);
            if (count($test) < 1) {
                echo "<div>No tests defined for selected subject.</div>";
                return;
            }
            $test = $test[0];
        }
    } else {
        $students = $subject->getStudents();
    }
    
    if (isset($_POST['form_serial']) && $_POST['form_serial'] == $_SESSION['form_serial'] - 1) {
        /* Deal with submitted data */
    }
}

?>
<!doctype html>
<html>

<head>
<?php require "bin/head.php"; ?>
</head>

<body>
	<div class="container">
	  <div id="top-part">
		<?php require "bin/navbar.php"; ?>
		<form method="GET">
    		<div class="form-group row">
    			<label for="subject" class="col-2 col-form-label">Subject</label>
        				<?php
        				if (!isset($_GET['subject'])) {
        				    echo '<div class="col-10">';
        				    echo '<select class="form-control" id="subject" name="subject" onchange="this.form.submit()">';
        				    echo "<option value=\"\" selected>Please select subject</option>";
            				foreach ($allSubjects as $s) {
            				    if (sizeof($s->getTests()) == 0) {
            				        continue;
            				    }
            				    echo "<option value=\"" . $s->getId() . "\">" . $s->getName() . "</option>";
            				}
            				echo '</select>';
        				} else {
        				    echo '<div class="col-10 col-form-label">';
        				    echo '<input type="hidden" id="subject" name="subject" value="' . $_GET['subject'] . '">';
        				    $subjectName = Subject::retrieveByDetail(Subject::ID, $_GET['subject']);
        				    if (sizeof($subjectName) !== 1) {
        				        die("Oops, somehow you have put in an invalid Subject");
        				    }
        				    $subjectName = $subjectName[0]->getName();
        				    echo "$subjectName (<a href=\"" . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) . "\">Change this</a>)";
        				}
        				?>
        		</div>
        		<?php if (isset($_GET['subject'])) {
        		    echo <<< EOF
            		<label for="teaching_group" class="col-2 col-form-label">Teaching group</label>
            		<div class="col-10">
            			<select class="form-control" id="teaching_group" name="teaching_group" onchange="this.form.submit()">
EOF;
                		    if (!isset($_GET['teaching_group'])) {
                		        echo "<option value=\"\" selected>Please select a group</option>";
                		    }
            				foreach ($teachingGroups as $g) {
            				    if (isset($_GET['teaching_group']) && $_GET['teaching_group'] === $g->getId()) {
            				        $selected = "selected";
            				    } else {
            				        $selected = "";
            				    }
            				    echo "<option value=\"" . $g->getId() . "\" $selected>" . $g->getName() . "</option>";
            				}
            				echo <<< EOF
            			</select>
          			</div>
EOF;
    		        if (isset($_GET['teaching_group'])) {
    		            echo <<< EOF
    		            <label for="test" class="col-2 col-form-label">Test</label>
    		            <div class="col-10">
    		            <select class="form-control" id="test" name="test" onchange="this.form.submit()">
EOF;
    		            if (!isset($_GET['test'])) {
    		                echo "<option value=\"\" selected>Please select a test</option>";
    		            }
    		            foreach ($tests as $t) {
    		                if (isset($_GET['test']) && $_GET['test'] === $t->getId()) {
    		                    $selected = "selected";
    		                } else {
    		                    $selected = "";
    		                }
    		                echo "<option value=\"" . $t->getId() . "\" $selected>" . $t->getName() . "</option>";
    		            }
    		            echo <<< EOF
            			</select>
          			</div>
EOF;
    		        }
        		} /* isset($_GET['subject']) */ ?>
    		</div>
		</form>
	  </div> <!-- #top-part -->

		<?php
		if (isset($test)) {
		    /* So, do we have any papers? */
		    $page_num = $_GET['page'] ?? 0;
		    $student_number = $_GET['student_number'] ?? 0;
		    /* If we get to the end of the student list, we try from the beginning for the next page.  If we wrap again, we have finished. */
		    $wrapped = false;
		    for (;;) {
		        if (isset($students[$student_number])) {
		            $student = $students[$student_number];
		            $scannedTest = ScannedTest::retrieveByDetails(
		                [ScannedTest::STUDENT_ID, ScannedTest::TEST_ID],
		                [$student->getId(), $test->getId()]);
		            if (count($scannedTest) != 0) {
		                $testPages = $scannedTest[0]->getPages();
		                if (!isset($testPages[$page_num])) {
		                    die("No more pages or students");
		                }
		                $testPage = $testPages[$page_num];
		                // Do not put up a test for marking until the timer is up.
		                // Also, do not show a page if it's already been marked and I've ticked to hide already marked pages.
		                if ($scannedTest[0]->secondsRemaining() > 0 ||
		                    ($testPage->get(ScannedTestPage::PAGE_SCORE) != null && isset($_GET['skipMarked']))) {
		                    $student_number++;
		                    continue;
		                }
		                break;
		            }
		            $student_number++;
		        } else {
		            if ($wrapped) {
		                die("No more pages or students");
		            }
		            $wrapped = true;
		            $student_number = 0;
		            $page_num++;
		        }
		    }
		    $testPage = $testPages[$page_num];
            echo "<span id=\"savebar\"></span>";
            echo "<div class=\"form-inline form-group\">";
            echo "<label for=\"skipMarked\">Skip already marked tests: </label>";
            if (isset($_GET['skipMarked'])) {
                $skipMarked = 'selected';
            } else {
                $skipMarked = '';
            }
            echo "<input class=\"form-control\" type=\"checkbox\" id=\"skipMarked\" $skipMarked onchange=\"doButtons()\">";
            echo "<label for=\"score\">Total page score: </label>";
            echo "<input class=\"form-control\" type=\"number\" id=\"score\" value=\"{$testPage->get(ScannedTestPage::PAGE_SCORE)}\">";
		    echo '</div>';
		    echo '<br /><br />';
		    echo '<div id="testpage"></div>';
		    echo '</div>';
		}
?>

<script>
    options = {
    		  width: $('.container')[0].clientWidth,
    		  height: $('.container')[0].clientWidth * 1.414,
    		  color: "red",           // Color for shape and text
    		  type : "circle",    // default shape: can be "rectangle", "arrow" or "text"
			  tools: ['undo', 'unselect', 'circle', 'arrow', 'pen', 'redo'], // Tools
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

    
	$(document).ready(function(){
	  $('#testpage').annotate(options);
      doButtons();
	  document.getElementById('top-part').style.display = 'none';
	  $(document).keydown( function(event) {
		  if (event.which === 13) {
		    // "Submit"
			save();
		  } else /* if (event.keyCode >= 48 && event.keyCode <= 57) */
			  if (event.data >= 0 || event.data <= 9) {
			if (document.activeElement.id !== 'score') {
				document.getElementById('score').value = '';
				document.getElementById('score').focus();
			}
		  }
		});
	});

	function doButtons() {
		getvars = 'subject=<?= $_GET['subject'] ?>&teaching_group=<?= $_GET['teaching_group'] ?>&test=<?= $_GET['test'] ?>';
		if (document.getElementById('skipMarked').checked) {
			getvars += '&skipMarked=yes';
		}
	    savebutton = '<a class="btn btn-success" onclick="save()">Save this page</a>';
	    dontsavebutton = '<a class="btn btn-danger" onclick="dontsave()">Do not save</a>';
		currentPage = <?= $page_num ?>;
	    prevbutton = '<a class="btn btn-secondary" onclick="visibleTop()">Select a different test/class</a><a class="btn btn-danger" href="?' + getvars + '&page=' + currentPage + '&student_number=<?= $student_number-1 ?>"><i class="fa fa-arrow-left"></i>Previous student</a>';
	    if (currentPage > 0) {
	        prevbutton += '<a class="btn btn-warning" href="?' + getvars + '&page=' + (currentPage-1) + '&student_number=<?= $student_number ?>"><i class="fa fa-arrow-up"></i>Previous page</a>';
	    }
	    nextbutton = '<a class="btn btn-success" href="?' + getvars + '&page=<?= ($page_num + 1) ?>&student_number=<?= $student_number ?>">Next page<i class="fa fa-arrow-down"></i></a>';
	    nextbutton += '<a class="btn btn-primary" href="?' + getvars + '&page=<?= $page_num ?>&student_number=<?= $student_number + 1 ?>">Next student<i class="fa fa-arrow-right"></i></a>';
	    document.getElementById('savebar').innerHTML = savebutton + prevbutton + nextbutton;
	}

	function dontsave() {
		document.getElementById('savebar').innerHTML = savebutton + prevbutton + nextbutton;
	}
	
	function save() {
		$('#testpage').annotate('export', options, function(image) {
			  var xhr = new XMLHttpRequest();
			  xhr.open("POST", 'students/submitimg.php', true);

			  //Send the proper header information along with the request
			  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			  xhr.onreadystatechange = function() {
				    if (this.readyState == 4 && this.status == 200) {
				      saved();
				    }
				};
			  scorepart = '';
			  if (document.getElementById('score').value !== '') {
				scorepart = "pagescore=" + document.getElementById('score').value;
			  }
			  xhr.send(scorepart + "&img=" + image + "&stpid=<?= $testPage->getId() ?>");
		  });
	}

	function saved() {
		document.getElementById('savebar').innerHTML = "Saved! " + prevbutton + nextbutton;
		if (scorepart !== '') {
			location.href = '?' + getvars + '&page=<?= $page_num ?>&student_number=<?= $student_number + 1 ?>';
		}
	}

	function visibleTop() {
		  document.getElementById('top-part').style.display = 'inline';
	}
</script>
</body>
</html>