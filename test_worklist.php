<?php
namespace TestAnalysis;

require "bin/classes.php";
require "dev/upgrade_database.php";

if (!isset($_GET['testId'])) {
    $tests = [];
    foreach (Test::retrieveAll() as $test) {
        $tests[$test->getId()] = $test;
    }
    $unfinishedScannedTests = [];
    
    $markedTests = [];
    $unmarkedTests = [];
    
    foreach (ScannedTest::retrieveByDetail(ScannedTest::STAFF_ID, $_SESSION['staff']->getId()) as $st) {
        if ($st->secondsRemaining() > 0) {
            $student_id = $st->get(ScannedTest::STUDENT_ID);
            if (!isset($unfinishedScannedTests[$student_id])) {
                $unfinishedScannedTests[$student_id] = [];
            }
            array_push($unfinishedScannedTests[$student_id], $tests[$st->get(ScannedTest::TEST_ID)]);
            continue;
        }
        $marked = true;
        foreach ($st->getPages() as $page) {
            if (is_null($page->get(ScannedTestPage::PAGE_SCORE))) {
                $marked = false;
                break;
            }
        }
        if ($marked) {
            if (!isset($markedTests[$st->get(ScannedTest::TEST_ID)])) {
                $markedTests[$st->get(ScannedTest::TEST_ID)] = 0;
            }
            $markedTests[$st->get(ScannedTest::TEST_ID)]++;
        } else {
            if (!isset($unmarkedTests[$st->get(ScannedTest::TEST_ID)])) {
                $unmarkedTests[$st->get(ScannedTest::TEST_ID)] = 0;
            }
            $unmarkedTests[$st->get(ScannedTest::TEST_ID)]++;
        }
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
		<?php require "bin/navbar.php"; ?>
		<table class="table table-hover">
			<thead>
                <tr>
                    <th>Unmarked tests</th>
                    
                    <th>Quantity</th>
                </tr>
			</thead>
			<?php 
			foreach ($unmarkedTests as $testId => $num) {
			    $test = $tests[$testId];
			    echo <<<EOF
        <tr>
            <td><a href="test_mark.php?my_tests_only=1&test=$testId">{$test->getName()}</a></td>

            <td>$num</td>
        </tr>
EOF;
			}
			?>
            <tr>
                <th>Marked tests</th>
                
                <th>Quantity</th>
            </tr>
			<?php
			foreach ($markedTests as $testId => $num) {
			    $test = $tests[$testId];
			    echo <<<EOF
        <tr>
            <td>{$test->getName()}</td>
            
            <td>$num</td>
        </tr>
EOF;
			}
			?>
			<tr>
				<th>Student name</th>
				
				<th>Incomplete test</th>
			</tr>
			<?php
			foreach ($unfinishedScannedTests as $studentId => $tests) {
			    $student = Student::retrieveByDetail(Student::ID, $studentId)[0];
			    foreach ($tests as $t) {
                    echo <<<EOF
        <tr>
            <td>{$student->getName()}</td>
            
            <td>{$t->getName()}</td>
        </tr>
EOF;
			    }
			}
			?>
		</table>
	</div>
</body>
</html>