<?php
namespace TestAnalysis;

$pageTitle = "Upload completed tests";

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

// Deal with naming
if (isset($_POST['scannedtestid']) && isset($_POST['student_id'])) {
    $scannedtest = ScannedTest::retrieveByDetail(ScannedTest::ID, $_POST['scannedtestid'])[0];
    $scannedtest->setStudentId($_POST['student_id']);
    $scannedtest->commit();
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
$tests_to_name = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::STAFF_ID], [-1, $staff->getId()]);

if (count($tests_to_name) < 1) {
    // Done!
    header("location: test_mark.php?subject={$_GET['subject']}&teaching_group={$_GET['teaching_group']}&test={$_GET['test']}");
}

// Prune students with tests assigned
foreach ($students as $n => $s) {
    if (count(ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$s->getId(), $test->getId()])) > 0) {
        // Student already has a test
        unset($students[$n]);
    }
}

if (count($students) < 1) {
    // Done!
    header("location: test_mark.php?subject={$_GET['subject']}&teaching_group={$_GET['teaching_group']}&test={$_GET['test']}");
}

?>
<!doctype html>
<html>

<head>
<?php require "bin/head.php"; ?>
</head>

<body>
	<div class="container">
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

		<?php
		// Show the next test and the students' names without tests assigned
		$test_to_name = null;
		foreach ($tests_to_name as $st) {
	        if ($st->get(ScannedTest::TEST_ID) == $test->getId()) {
	            $test_to_name = $st;
	            break;
		    }
    	}
		
		if (is_null($test_to_name)) {
		    $subjectId = $tests_to_name[0]->get(ScannedTest::SUBJECT_ID);
		    $testId = $tests_to_name[0]->get(ScannedTest::TEST_ID);
		    $sname = Subject::retrieveByDetail(Subject::ID, $subjectId)[0]->getName();
		    $tname = Test::retrieveByDetail(Test::ID, $testId)[0]->getName();
		    echo "<div>You have some unnamed tests, but not for this subject.  Try this next:</div>";
		    echo "<div><ul>";
		    echo "<li>$sname</li>";
		    echo "<li>$tname</li>";
		    echo "</ul></div>";
		    die();
		}
		
		$firstPageId = ScannedTestPage::retrieveByDetails(
		    [ScannedTestPage::SCANNEDTEST_ID, ScannedTestPage::PAGE_NUM],
		    [$test_to_name->getId(), 0]
		)[0]->getId();
		
		echo "<form method=\"POST\">";
		echo "<div class=\"row\">";
		echo "<div class=\"form-group col-12\"><label for=\"student_id\">Select student's name</label>";
		echo "<select class=\"form-control\" name=\"student_id\" id=\"student_id\">";
		foreach ($students as $s) {
		    echo "<option value=\"{$s->getId()}\">{$s->getName()}</option>";
		}
		echo "</select></div>";
		echo "</div>";
		echo "<div class=\"row\"><div class=\"col-12\"><input type=\"submit\" class=\"btn btn-primary form-control\" value=\"Save\"></div></div>";
		echo "<div class=\"row\"><div class=\"col-12\">";
		echo "<img class=\"img-fluid\" src=\"async/getScannedImage.php?stpid=$firstPageId\">";
		echo "<input type=\"hidden\" name=\"scannedtestid\" value=\"{$test_to_name->getId()}\">";
        echo "</div></div>";
        echo "</form>";
?>
</div>
</body>
</html>