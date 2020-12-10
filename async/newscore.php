<?php
namespace TestAnalysis;

require "../bin/classes.php";

if (!isset($_POST['studentId']) || !isset($_POST['testId'])) {
    die("Why are you trying to open this?");
}
// studentId=" + studentId + "&testId=" + testId + "&a=" + resultA + "&b=" + resultB + "&subjectId=" + $subjectId

$studentId = $_POST['studentId'];

if (!Config::is_staff($auth_user)) {
    die("nope");
}

$subject = Subject::retrieveByDetail(Subject::ID, $_POST['subjectId'])[0];
$student = Student::retrieveByDetail(Student::ID, $_POST['studentId'])[0];
$t = Test::retrieveByDetail(Test::ID, $_POST['testId']);

if (empty($t)) {
    die("Other failure");
}

$t = $t[0];

if ($_POST['a'] > $t->get(Test::TOTAL_A) || $_POST['b'] > $t->get(Test::TOTAL_B)) {
    die("Total out of range");
}

$oldResult = $t->getResult($student);

$result = new TestResult([
    TestResult::ID => null,
    TestResult::SCORE_A => $_POST['a'],
    TestResult::SCORE_B => $_POST['b'],
    TestResult::STUDENT_ID => $_POST['studentId'],
    TestResult::TEST_ID => $_POST['testId']
]);

$result->commit();

// If the old result was put in less than five minutes ago, we'll just overwrite

if (!is_null($oldResult) && strtotime($oldResult->get(TestResult::RECORDED_TS)) + 43200 > time()) {
    echo "Deleting {$oldResult->getId()}";
    TestResult::delete($oldResult->getId());
}

if ($t->get(Test::TOTAL_A) > 0) {
    echo ("percent-{$t->getId()}-$studentId:" . round($_POST['a'] * 100 / $t->get(Test::TOTAL_A), 0) . ",");
} else {
    echo ("percent-{$t->getId()}-$studentId:" . round($_POST['b'] * 100 / $t->get(Test::TOTAL_B), 0) . ",");
}

$grade = $t->calculateGrade($result, $subject);

echo ("grade-{$t->getId()}-$studentId:$grade");