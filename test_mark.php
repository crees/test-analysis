<?php
namespace TestAnalysis;

$pageTitle = "Mark tests";

require "bin/classes.php";
require "dev/upgrade_database.php";

$staff = Staff::me($auth_user);

$departments = $staff->getDepartments(true);
$allSubjects = [];
foreach ($departments as $d) {
    foreach (Subject::retrieveByDetail(Subject::DEPARTMENT_ID, $d->getId(), Subject::NAME) as $s) {
        $s->setName("{$d->getName()}: {$s->getName()}");
        array_push($allSubjects, $s);
    }
}

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
    
    if (isset($_POST['form_serial']) && (session_status() != PHP_SESSION_ACTIVE || $_POST['form_serial'] != $_SESSION['form_serial'] - 1)) {
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
		    $i = 0;
		    $scannedTests = $scannedTest = ScannedTest::retrieveByDetail(
		        ScannedTest::TEST_ID,
		        $test->getId()
		    );
		    // Remove students from the list with no tests or unfinished tests
		    foreach ($students as $i => $s) {
		        foreach ($scannedTests as $st) {
		            // Do not put up a test for marking until the timer is up.
		            if ($st->get(ScannedTest::STUDENT_ID) == $s->getId() && $st->secondsRemaining() == 0) {
		                $s->setLabel("scannedTest", $st);
		            }
		        }
		        if (is_null($s->getLabel("scannedTest")))
		            unset($students[$i]);
		    }
		    if (empty($students)) {
		        die("No tests completed for this group");
		    }
		    // Close gaps
		    $students = array_values($students);
		    $page_num = $_GET['page'] ?? 0;
		    $student_number = $_GET['student_number'] ?? 0;
		    $pageFound = false;
		    while (!$pageFound) {
		        if (isset($students[$student_number])) {
		            $student = $students[$student_number];
		            $scannedTest = $student->getLabel("scannedTest");
	                $testPages = $scannedTest->getPages();
	                if (!isset($testPages[$page_num])) {
	                    if (isset($teaching_group)) {
	                        $link = "<a class=\"btn btn-success\" href=\"test_scanned_scores.php?subject={$subject->getId()}&teaching_group={$teaching_group}&test={$test->getId()}\">Get results</a>";
	                    } else {
	                        $link = "<a class=\"btn btn-success\" href=\"test_worklist.php\">Back to the worklist</a>";
	                    }
	                    die ("<div>No more tests remaining.  $link</div>");
	                }
	                $testPage = $testPages[$page_num];
	                // Do not show a page if it's already been marked and I've ticked to hide already marked pages.
	                if ($testPage->get(ScannedTestPage::PAGE_SCORE) != null && isset($_GET['skipMarked'])) {
	                    $student_number++;
	                    continue;
	                } else {
	                    $pageFound = true;
	                }
		        } else {
		            $student_number = 0;
		            $page_num++;
		        }
		    }
		    $testPage = $testPages[$page_num];
		    echo "<div class=\"row\">";
		    if ($staff->get(Staff::LARGE_MARKING) == 1) {
		        echo '<div class="col-12"><div class="savebar"></div></div>';
		        echo '<div class="col-11" id="testpage-container"><div id="testpage"></div></div><div class="col-1"><div class="toolbar-container" style="position: sticky; top: 0px; z-index: 100;"></div></div>';
                $savebarClass = "col-12";
		    } else {
		        echo '<div class="col-lg-9"><div id="testpage"></div></div>';
		        $savebarClass = "col-lg-3";
		    }
		    echo "<div class=\"$savebarClass\">";
		    echo "<div class=\"savebar\"></div><br>";
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
		    echo "<div onclick=\"$('span#kidname')[0].style.display = 'inline';\">Student name (press ?): <span id=\"kidname\" style=\"display: none\">{$student->getName()}</span><br />Every tick placed increments the total by one.  Press Enter to save, or type a number (no clicks necessary) to jump to the score box.  Spacebar also saves.  Mark title page as zero!</div>";
            echo "<div>Shortcut keys:<dl class=\"row\">";
            $defs = [
                ["Key", "Function", "<span class=\"font-weight-bold\">Definition</span>"],
                ["\\", "Undo", "Remove the last annotation (but does not deduct marks-- check your total!)"],
                ["z", "Tick", "Correct"],
                ["Z", "(Tick)", "Correct, but no mark"],
                ["x", "Cross", "Incorrect"],
                ["c", "ECF", "Error carried forward"],
                ["v", "TV", "Too vague"],
                ["b", "BOD", "Benefit of doubt"],
                ["n", "NAQ", "Not answered question"],
                ["m", "&#x2227;", "Something missing here"],
                [",", "REP", "Student has repeated a marking point"],
                ["q", "L1", "Answer is Level 1 (for 6/9 markers)"],
                ["w", "L2", "Answer is Level 2 (for 6/9 markers)"],
                ["e", "L3", "Answer is Level 3 (for 6/9 markers)"],
            ];
            foreach ($defs as $def) {
                echo "<dt class=\"col-2\">{$def[0]}</dt>";
                echo "<dt class=\"col-3\">{$def[1]}</dt>";
                echo "<dd class=\"col-7\">{$def[2]}</dd>";
            }
            echo "</dl></div>";
		    echo '</div>';
		    echo "</div>";
		    echo '</div>';
		}
?>

<script>
default_tool = '<?= $staff->get(Staff::DEFAULT_MARKING_TOOL); ?>';
    options = {
<?php // Deal with staff who want the test to take the width of the browser window
if ($staff->get(Staff::LARGE_MARKING) == 1) {
?>
              //width: Math.trunc($('html')[0].clientWidth * 0.8),
              //height: Math.trunc($('html')[0].clientWidth * 0.8 * 1.414),
              width: Math.trunc($('#testpage-container')[0].clientWidth),
              height: Math.trunc($('#testpage-container')[0].clientWidth * 1.414),
              position: "vertical",
			  toolbarContainer: '.toolbar-container',
<?php } else { ?>
    		  width: Math.trunc($('html')[0].clientHeight * 0.95 / 1.414),
			  height: Math.trunc($('html')[0].clientHeight * 0.95),
    		  position: "right",       // Position of toolbar (available only with bootstrap)
<?php } ?>
			  color: "red",           // Color for shape and text
    		  type: default_tool,
			  // for the stamps, stamp_\u2227 is logical AND symbol, basically a huge caret for "missing"
			  tools: ['undo', 'tick', 'stamp_(\u2713)', 'cross', 'stamp_ECF', 'stamp_TV', 'stamp_BOD', 'stamp_NAQ', 'stamp_\u2227', 'stamp_REP', 'stamp_L1', 'stamp_L2', 'stamp_L3', 'text', 'rectangle-filled', 'circle', 'arrow', 'pen', 'redo'], // Tools
    		  images: ["async/getScannedImage.php?stpid=<?= $testPage->getId() ?>"],          // Array of images path : ["images/image1.png", "images/image2.png"]
    		  linewidth: 2,           // Line width for rectangle and arrow shapes
<?php // Deal with staff who want the test to take the width of the browser window
if ($staff->get(Staff::LARGE_MARKING) == 1) {
?>
    		  fontsize: Math.trunc($('html')[0].clientWidth * 0.022) + "px",       // font size for text
			  lineheight: Math.trunc($('html')[0].clientWidth * 0.022),
<?php } else { ?>
              fontsize: Math.trunc($('html')[0].clientHeight * 0.022) + "px",       // font size for text
              lineheight: Math.trunc($('html')[0].clientHeight * 0.022),
<?php } ?>
    		  bootstrap: true,       // Bootstrap theme design
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
				  setSaveBar(savebutton + dontsavebutton);
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
		  switch (event.key) {
		    case '0': case '1': case '2': case '3': case '4':
		    case '5': case '6': case '7': case '8': case '9':
		    	if (document.activeElement.id !== 'score') {
			    	document.getElementById('score').value = '';
					document.getElementById('score').focus();
		    	}
				break;
		    case ' ':
			    if (document.getElementById('score').value == '') {
			    	document.getElementById('score').value = 0;
			    }
			    // FALLTHROUGH
		    case 'Enter':
		    	event.preventDefault();
				save();
				break;
		    case '\\':
		    	tool = $("button#undoaction");
		    	break;
		  	case 'z':
		    	tool = $("[name='tool_option_testpage'][data-tool='tick']");
		    	break;
		  	case 'Z':
			  	tool = $("[name='tool_option_testpage'][data-tool='stamp_(\u2713)']");
			  	break;
		  	case 'x':
			  	tool = $("[name='tool_option_testpage'][data-tool='cross']");
			  	break;
		  	case 'c':
			  	tool = $("[name='tool_option_testpage'][data-tool='stamp_ECF']");
			  	break;
		  	case 'v':
		    	tool = $("[name='tool_option_testpage'][data-tool='stamp_TV']");
		    	break;
		  	case 'b':
		    	tool = $("[name='tool_option_testpage'][data-tool='stamp_BOD']");
		    	break;
		  	case 'n':
			  	tool = $("[name='tool_option_testpage'][data-tool='stamp_NAQ']");
			  	break;
		  	case 'm':
			  	tool = $("[name='tool_option_testpage'][data-tool='stamp_\u2227']");
			  	break;
		  	case ',':
		  		tool = $("[name='tool_option_testpage'][data-tool='stamp_REP']");
			  	break;
		  	case 'q':
			  	tool = $("[name='tool_option_testpage'][data-tool='stamp_L1']");
			  	break;
		  	case 'w':
			  	tool = $("[name='tool_option_testpage'][data-tool='stamp_L2']");
			  	break;
		  	case 'e':
			  	tool = $("[name='tool_option_testpage'][data-tool='stamp_L3']");
			  	break;
		  	case 'h':
			  	tool = $("a#button-left")[0];
			  	break;
		  	case 'j':
			  	tool = $("a#button-down")[0];
			  	break;
		  	case 'k':
			  	tool = $("a#button-up")[0];
			  	break;
		  	case 'l':
			  	tool = $("a#button-right")[0];
			  	break;
		  	case '?':
			  	$('span#kidname')[0].style.display = 'inline';
			  	break;
			default:
				break;
		  }
		  if (tool != null) {
		  	tool.click();
		  }
		});
	});

	function setSaveBar(contents) {
		for (bar of $('div.savebar')) {
			bar.innerHTML = contents;
		}
	}

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
			prevbutton = '<a class="btn btn-danger" id="button-left" href="?' + getvars + '&page=' + currentPage + '&student_number=<?= $student_number-1 ?>"><i class="fa fa-arrow-left"></i>Previous student (h)</a>';
		} else {
			prevbutton = '<a class="btn btn-secondary"><i class="fa fa-arrow-left"></i>Previous student</a>';
		}
		if (currentPage > 0) {
	        prevbutton += '<a class="btn btn-warning" id="button-up" href="?' + getvars + '&page=' + (currentPage-1) + '&student_number=<?= $student_number ?>"><i class="fa fa-arrow-up"></i>Previous page (k)</a>';
	    }
	    nextbutton = '<a class="btn btn-success" id="button-down" href="?' + getvars + '&page=<?= ($page_num + 1) ?>&student_number=<?= $student_number ?>">(j) Next page<i class="fa fa-arrow-down"></i></a>';
	    nextbutton += '<a class="btn btn-primary" id="button-right" href="?' + getvars + '&page=<?= $page_num ?>&student_number=<?= $student_number + 1 ?>">(l) Next student<i class="fa fa-arrow-right"></i></a><a class="btn btn-secondary" onclick="visibleTop()">Change test/class</a>';
		setSaveBar(savebutton + prevbutton + nextbutton);
	}

	function dontsave() {
		setSaveBar(savebutton + prevbutton + nextbutton);
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
		setSaveBar("Saved! " + prevbutton + nextbutton);
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
