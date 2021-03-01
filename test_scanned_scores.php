<?php
namespace TestAnalysis;

$pageTitle = "Manage and score tests";

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

function studentNameRow(Student $s) {
    $id = $s->getId();
    $name = $s->getName();
    $firstName = $s->get(Student::FIRST_NAME);
    $username = $s->get(Student::USERNAME);
    if (!empty($username)) {
        $eye = "<a href=\"" . Config::site_url . "/students/?masquerade=$username\" title=\"Login as $username\">&#x1F50D;</a>";
    } else {
        $eye = "";
    }
    return "<a href=\"student_individual_scores.php?student=$id\" title=\"$firstName's mark history\">&#x023F0</a> $eye $name";
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
}

?>
<!doctype html>
<html>

<head>
<?php require "bin/head.php"; ?>
</head>

<body onload="redrawTiming(null, null);">
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

if (empty($students) || !isset($test)) {
    die();
}

$maxPages = 0;
$scannedTests = [];
$scannedTests_unmarked = [];
foreach ($students as $s) {
    $scannedTest = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$s->getId(), $test->getId()]);
    if (!isset($scannedTest[0])) {
        continue;
    }
    $pages = $scannedTest[0]->getPages();
    // Don't include papers with missing scores
    $score_missing = false;
    foreach ($pages as $page) {
        if (is_null($page->get(ScannedTestPage::PAGE_SCORE))) {
            $score_missing = true;
            break;
        }
    }
    if ($score_missing) {
        array_push($scannedTests_unmarked, $scannedTest[0]);
        continue;
    }
    if (count($pages) > $maxPages) {
        $maxPages = count($pages);
    }
    array_push($scannedTests, $scannedTest[0]);
}

echo "<div class=\"table-responsive table-95 table-stickyrow\">";
echo "<table class=\"table table-bordered table-sm table-hover\">";
echo "<tr>";
echo "<th>Name</th>";
echo "<th>Timer operations</th>";
echo "<th>Student can upload own pdf</th>";
for ($i = 1; $i < $maxPages+1; $i++) {
    echo "<th scope=\"col\"><a href=\"test_mark.php?subject={$subject->getId()}&teaching_group=$teaching_group&test={$test->getId()}&page=" . ($i-1) . "\">Page $i</a></th>";
}
echo "<th>Total</th>";
echo "<th>Save score to database</th>";
echo "</tr>";

foreach ($students as $s) {
    $scannedTest = null;
    foreach ($scannedTests as $st) {
        if ($st->get(ScannedTest::STUDENT_ID) == $s->getId()) {
            $scannedTest = $st;
            break;
        }
    }
    if (is_null($scannedTest)) {
        foreach ($scannedTests_unmarked as $stu) {
            if ($stu->get(ScannedTest::STUDENT_ID) == $s->getId()) {
                echo "<tr><th scope=\"row\">{$s->getName()}</th>";
                echo "<td class=\"bta-timer\" id=\"{$stu->getId()}\"></td>";
                echo "<td class=\"bta-upload_allowed\" id=\"{$stu->getId()}\"></td>";
                echo "<td>";
                if (is_null($stu->get(ScannedTest::TS_STARTED))) {
                    echo "&#x1f4d5; Not started";
                } else if ($stu->secondsRemaining() > 0) {
                    echo "&#x1f4d6; Started, but not finished";
                } else {
                    echo "&#x2611; &#x2611; &#x2610; &#x2610; Not yet completely marked";
                }
                echo "</td>";
                echo "</tr>";
            }
        }
        continue;
    }
    echo "<tr><th scope=\"row\">" . studentNameRow($s) . "</th><td>&nbsp;</td><td>&nbsp;</td>";
    $pagesLeft = $maxPages;
    $canCommit = true;
    $total = 0;
    $sectionTotal = [];
    foreach ($scannedTest->getPages() as $page) {
        $pagesLeft--;
        $pTotal = $page->get(ScannedTestPage::PAGE_SCORE);
        $total += $pTotal;
        echo "<td>$pTotal</td>";
        if ($canCommit) {
            if (is_null($page->get(ScannedTestPage::TESTCOMPONENT_ID))) {
                $canCommit = false;
            } else {
                $tcId = $page->get(ScannedTestPage::TESTCOMPONENT_ID);
                $sectionTotal[$tcId] = ($sectionTotal[$tcId] ?? 0) + $pTotal;
            }
        }
    }
    while ($pagesLeft-- > 0) {
        echo "<td>-</td>";
    }
    echo "<td>$total</td>";
    if ($canCommit) {
        $args = [];
        $scoreChanged = false;
        $resultsRecorded = false;
        foreach ($sectionTotal as $id => $total) {
            array_push($args, "\\\"$id\\\": $total");
            $results = TestComponentResult::retrieveByDetails(
                [TestComponentResult::STUDENT_ID, TestComponentResult::TESTCOMPONENT_ID],
                [$s->getId(), $id],
                TestComponentResult::RECORDED_TS . ' DESC');
            if (isset($results[0])) {
                $resultsRecorded = true;
                if ($results[0]->get(TestComponentResult::SCORE) != $total) {
                    $scoreChanged = true;
                }
            }
        }
        $args = '{' . implode(", ", $args) . '}';
        if ($resultsRecorded == false || $scoreChanged == true) {
            echo "<td><input class=\"form-control\" type=\"button\" id=\"commit-{$s->getId()}\" onclick='commit({$s->getId()}, \"$args\")' value=\"Commit score\"></td>";
        } else {
            echo "<td><input class=\"form-control\" type=\"button\" disabled value=\"Commit score (score already present for this student, click name to check)\"></td>";
        }
    }
    echo "</tr>";
}
echo "</table></div>";
?>

</div>

<script>
function commit(studentId, values) {
	values = JSON.parse(values);
	for (testComponentId in values) {
		var xhr = new XMLHttpRequest();
	    xhr.open("POST", 'async/newscore.php', true);
	    
	    //Send the proper header information along with the request
	    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	    xhr.onreadystatechange = function() {
	        if (this.readyState == 4 && this.status == 200) {
	          saved(studentId);
	        }
	    };
	    xhr.send("studentId=" + studentId + "&testComponentId=" + testComponentId + "&result=" + values[testComponentId] + "&subjectId=<?= $subject->getId() ?>");
	}
}

function saved(studentId) {
	button = $('input#commit-' + studentId)[0];
	button.disabled = true;
}

function editableTiming(studentId, value) {
	cell = $('#' + studentId + '.bta-timer')[0];
	cell.classList.add('nopadding');
	cell.innerHTML = '<input class="form-control border-0 px-1 timingBox" id="' + studentId + '" type="number" value="' + value + '" onblur="redrawTiming(' + studentId + ', this.value);">';
	box = $('#' + studentId + '.timingBox')[0];
	box.focus();
	box.select();
}

function redrawTiming(stId, newTime) {
	if (stId !== null) {
		timerId = '#' + stId;
	} else {
		timerId = '';
	}
	if (newTime === -1) {
		return;
	}
	for (cell of $('td.bta-timer' + timerId)) {
		scannedTestId = parseInt(cell.id);
		var xhr = new XMLHttpRequest();
	    xhr.open("POST", 'async/scannedTestTimer.php', true);
	    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	    xhr.onreadystatechange = function() {
		    console.log(this.responseText);
	        if (this.readyState == 4 && this.status == 200) {
			  [id, remainingTime, ts_started, upload_allowed] = this.responseText.split(':');
			  remainingTime = parseInt(remainingTime);
		      innerHTML  = '<span class="text-danger" onclick="redrawTiming(' + id + ', ' + (remainingTime-1) + ')">&#x21e9;</span>';
		      innerHTML += '<span class="editableTiming" id="' + id + '" onclick="editableTiming(' + id + ', ' + remainingTime + ');">' + remainingTime + '</span>';
		      innerHTML += '<span class="text-success" onclick="redrawTiming(' + id + ', ' + (remainingTime+1) + ')">&#x21e7;</span>';
			  innerHTML += ' min';
			  if (ts_started !== '') {
				  innerHTML += '<span class="text-success" onclick="redrawTiming('  + id + ', -2)">(reset)</span>';
			  } else {
				  innerHTML += '<span class="text-warning" onclick="redrawTiming('  + id + ', 0)">(end test)</span>';
			  }
			  cell = $('td.bta-timer#' + id)[0];
			  cell.classList.remove('nopadding');
			  cell.innerHTML = innerHTML;
			  if (upload_allowed == 1) {
				  $('td#' + id + '.bta-upload_allowed')[0].innerHTML = '<span class="text-success" onclick="redrawTiming(' + id + ', -3)">&#x2601; allowed</span>';
			  } else {
				  $('td#' + id + '.bta-upload_allowed')[0].innerHTML = '<span class="text-danger" onclick="redrawTiming(' + id + ', -4)">&#x1f6ab; not allowed</span>';				  
			  }
	        }
	    };
	    if (newTime === null) {
		    // Query only
	    	xhr.send("scannedTestId=" + scannedTestId);
	    } else if (newTime === 0) {
		    xhr.send("scannedTestId=" + scannedTestId + "&forceEndTest=1");
	    } else if (newTime === -2) {
		    // magic number to just reset
			xhr.send("scannedTestId=" + scannedTestId + "&resetTimer=true");
	    } else if (newTime === -3) {
		    xhr.send("scannedTestId=" + scannedTestId + "&student_upload_allowed=0");
	    } else if (newTime === -4) {
	    	xhr.send("scannedTestId=" + scannedTestId + "&student_upload_allowed=1");
	    } else {
	    	xhr.send("scannedTestId=" + scannedTestId + "&newTime=" + newTime);
	    }
	}
}

</script>


</body>
</html>