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

echo "<table class=\"table table-bordered table-sm table-hover\">";
echo "<tr>";
echo "<th>Name</th>";
for ($i = 1; $i < $maxPages+1; $i++) {
    echo "<th>Page $i</th>";
}
echo "<th>Total</th>";
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
                echo "<tr><td>{$s->getName()}</td>";
                if (is_null($stu->get(ScannedTest::TS_STARTED))) {
                    echo "<td>Not started</td>";
                } else if ($stu->secondsRemaining() > 0) {
                    echo "<td>Started, but not finished</td>";
                } else {
                    echo "<td>Not yet completely marked</td>";
                }
                echo "</tr>";
            }
        }
        continue;
    }
    echo "<tr><td>{$s->getName()}</td>";
    $pagesLeft = $maxPages;
    $total = 0;
    foreach ($scannedTest->getPages() as $page) {
        $pagesLeft--;
        $pTotal = $page->get(ScannedTestPage::PAGE_SCORE);
        $total += $pTotal;
        echo "<td>$pTotal</td>";
    }
    while ($pagesLeft-- > 0) {
        echo "<td>-</td>";
    }
    echo "<td>$total</td>";
    echo "</tr>";
}
echo "</table>";
?>
</body>
</html>