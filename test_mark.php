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
} else if (isset($_GET['my_tests_only']) && $_GET['my_tests_only'] && isset($_GET['test'])) {
    $test = Test::retrieveByDetail(Test::ID, $_GET['test']);
    if (count($test) < 1) {
        echo "<div>This must be a bug of some sort, sorry.</div>";
        return;
    }
    $test = $test[0];
    $students = [];
    foreach (ScannedTest::retrieveByDetails([ScannedTest::TEST_ID, ScannedTest::STAFF_ID],
        [$_GET['test'],        $staff->getId()      ],
        ) as $st) {
            array_push($students, Student::retrieveByDetail(Student::ID, $st->get(ScannedTest::STUDENT_ID))[0]);
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
<?php if (!(isset($_GET['my_tests_only']) && $_GET['my_tests_only'])) { ?>
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
<?php } /* my_tests_only */ ?>
	  </div> <!-- #top-part -->

		<?php
		if (isset($test) && count($students) > 0) {
		    /* So, do we have any papers? */
		    $page_num = $_GET['page'] ?? 0;
		    $student_number = $_GET['student_number'] ?? 0;
		    $firstLoop = true;
		    for (;;) {
		        if (isset($students[$student_number])) {
		            $student = $students[$student_number];
		            $scannedTest = ScannedTest::retrieveByDetails(
		                [ScannedTest::STUDENT_ID, ScannedTest::TEST_ID],
		                [$student->getId(), $test->getId()]);
		            if (count($scannedTest) != 0) {
		                $testPages = $scannedTest[0]->getPages();
		                if (!isset($testPages[$page_num])) {
		                    if (isset($teaching_group)) {
		                        $link = "<a class=\"btn btn-success\" href=\"test_scanned_scores.php?subject={$subject->getId()}&teaching_group={$teaching_group}&test={$test->getId()}\">Get results</a>";
		                    } else {
		                        $link = "<a class=\"btn btn-success\" href=\"test_worklist.php\">Back to the worklist</a>";
		                    }
		                    die ("<div>No more tests remaining.$link</div>");
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
		            if ($firstLoop) {
    		            $student_number = 0;
    		            $page_num++;
    		            $firstLoop = false;
		            } else {
		                $page_num++;
		                die ("No tests have been set for this group.  This could be a bug-- if so, click <a href=\"?subject={$_GET['subject']}&teaching_group=$teaching_group&test={$_GET['test']}&page=$page_num&student_number=0\">here</a> for the next page.");
		            }
		        }
		    }
		    $testPage = $testPages[$page_num];
		    echo "<div class=\"row\">";
		    echo '<div class="col-lg-9"><div id="testpage"></div></div>';
		    echo "<div class=\"col-lg-3\">";
		    echo "<div id=\"savebar\"></div><br>";
		    echo "<div class=\"form-inline form-group\">";
            if (isset($_GET['skipMarked'])) {
                $skipMarked = 'checked';
            } else {
                $skipMarked = '';
            }
            echo "<input class=\"form-control\" type=\"checkbox\" id=\"skipMarked\" $skipMarked onchange=\"doButtons()\"><br>";
            echo "<label for=\"skipMarked\"> Skip already marked tests</label>";
            echo "<label for=\"score\">Total page score: </label>";
            echo "<input class=\"form-control\" type=\"number\" id=\"score\" value=\"{$testPage->get(ScannedTestPage::PAGE_SCORE)}\">";
		    echo '</div>';
		    echo "<div onclick=\"$('span#kidname')[0].style.display = 'inline';\">Student name (press ?): <span id=\"kidname\" style=\"display: none\">{$student->getName()}</span><br /> Shortcut keys: Z for ticks, X for crosses.  Every tick placed increments the total by one.  Press Enter to save, or type a number (no clicks necessary) to jump to the score box.  Mark title page as zero!</div>";
            echo '</div>';
		    echo "</div>";
		    echo '</div>';
		}
?>

<script>
    options = {
    		  width: Math.trunc($('html')[0].clientHeight * 0.95 / 1.414),
			  height: Math.trunc($('html')[0].clientHeight * 0.95),
			  color: "red",           // Color for shape and text
    		  type : "tick",    // default shape: can be "rectangle", "arrow" or "text"
			  tools: ['undo', 'tick', 'cross', 'text', 'rectangle-filled', 'circle', 'arrow', 'pen', 'redo'], // Tools
    		  images: ["async/getScannedImage.php?stpid=<?= $testPage->getId() ?>"],          // Array of images path : ["images/image1.png", "images/image2.png"]
    		  linewidth: 2,           // Line width for rectangle and arrow shapes
    		  fontsize: Math.trunc($('html')[0].clientHeight * 0.022) + "px",       // font size for text
			  lineheight: Math.trunc($('html')[0].clientHeight * 0.022),
    		  bootstrap: true,       // Bootstrap theme design
    		  position: "right",       // Position of toolbar (available only with bootstrap)
    		  selectEvent: "change", // listened event on .annotate-image-select selector to select active images
    		  unselectTool: false,   // Add a unselect tool button in toolbar (useful in mobile to enable zoom/scroll)
			  imageExport: { type: "image/jpg", quality: 1 },
			  onAnnotate: function(tool) {
				  if (tool === 'text') {
					  // Don't jump to text box while doing text!
					  usingTextBox = true;
				  } else {
					  usingTextBox = false;
				  }
				  if (tool === 'tick') {
					score = document.getElementById('score');
					if (score.value == '') {
						score.value = 1;
					} else {
						score.value = parseInt(score.value) + 1;
					}
				  }
				  document.getElementById('savebar').innerHTML = savebutton + dontsavebutton;
			  },
    };

    
	$(document).ready(function(){
	  $('#testpage').annotate(options);
      doButtons();
	  document.getElementById('top-part').style.display = 'none';
	  usingTextBox = false;
	  $(document).keydown( function(event) {
		  if (usingTextBox) {
			return 0;
		  }
		  tool = null;
		  if (event.which === 13) {
		    // "Submit"
			save();
		  } else /* if (event.keyCode >= 48 && event.keyCode <= 57) */
			  if (event.key >= 0 && event.key <= 9) {
			if (document.activeElement.id !== 'score') {
				document.getElementById('score').value = '';
				document.getElementById('score').focus();
			}
		  } else switch (event.key) {
		  	case 'z':
		    	tool = $("[name='tool_option_testpage'][data-tool='tick']");
		    	break;
		  	case 'x':
			  	tool = $("[name='tool_option_testpage'][data-tool='cross']");
			  	break;
		  	case '?':
			  	$('span#kidname')[0].style.display = 'inline';
			  	break;
			default:
				break;
		  }
		  if (tool != null) {
		  	tool.prop("checked", true);
		  	tool.click();
		  }
		});
	});

	function doButtons() {
		<?php if (isset($_GET['my_tests_only']) && $_GET['my_tests_only']) { ?>
		getvars = 'my_tests_only=1&test=<?= $_GET['test'] ?>';
		<?php } else { ?>
		getvars = 'subject=<?= $_GET['subject'] ?>&teaching_group=<?= $_GET['teaching_group'] ?>&test=<?= $_GET['test'] ?>';
		<?php } ?>
		if (document.getElementById('skipMarked').checked) {
			getvars += '&skipMarked=yes';
		}
	    savebutton = '<a class="btn btn-success" onclick="save()">Save page</a>';
	    dontsavebutton = '<a class="btn btn-danger" onclick="dontsave()">Do not save</a>';
		currentPage = <?= $page_num ?>;
		if (<?= $student_number ?> > 0) {
			prevbutton = '<a class="btn btn-danger" href="?' + getvars + '&page=' + currentPage + '&student_number=<?= $student_number-1 ?>"><i class="fa fa-arrow-left"></i>Previous student</a>';
		} else {
			prevbutton = '<a class="btn btn-secondary"><i class="fa fa-arrow-left"></i>Previous student</a>';
		}
		if (currentPage > 0) {
	        prevbutton += '<a class="btn btn-warning" href="?' + getvars + '&page=' + (currentPage-1) + '&student_number=<?= $student_number ?>"><i class="fa fa-arrow-up"></i>Previous page</a>';
	    }
	    nextbutton = '<a class="btn btn-success" href="?' + getvars + '&page=<?= ($page_num + 1) ?>&student_number=<?= $student_number ?>">Next page<i class="fa fa-arrow-down"></i></a>';
	    nextbutton += '<a class="btn btn-primary" href="?' + getvars + '&page=<?= $page_num ?>&student_number=<?= $student_number + 1 ?>">Next student<i class="fa fa-arrow-right"></i></a><a class="btn btn-secondary" onclick="visibleTop()">Change test/class</a>';
	    document.getElementById('savebar').innerHTML = savebutton + prevbutton + nextbutton;
	}

	function dontsave() {
		document.getElementById('savebar').innerHTML = savebutton + prevbutton + nextbutton;
	}
	
	function save() {
		$('#testpage').annotate('export', options, function(image) {
			  var xhr = new XMLHttpRequest();
			  xhr.open("POST", 'async/submitimg.php', true);

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