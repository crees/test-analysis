<?php
namespace TestAnalysis;

require "../bin/classes.php";

if (!isset($_POST['studentId']) || !isset($_POST['testComponentId'])) {
    die("Why are you trying to open this?");
}
// studentId=" + studentId + "&testComponentId=" + testComponentId + "&result=" + result + "&subjectId=" + $subjectId

$studentId = $_POST['studentId'];

if (!Config::is_staff($auth_user)) {
    die("nope");
}

$subject = Subject::retrieveByDetail(Subject::ID, $_POST['subjectId'])[0];
$student = Student::retrieveByDetail(Student::ID, $_POST['studentId'])[0];
$tc = TestComponent::retrieveByDetail(TestComponent::ID, $_POST['testComponentId']);

if (empty($tc)) {
    die("Other failure");
}

$tc = $tc[0];

$test = Test::retrieveByDetail(Test::ID, $tc->get(TestComponent::TEST_ID))[0];

if ($_POST['result'] > $tc->get(TestComponent::TOTAL)) {
    die("Total out of range");
}

$oldResult = TestComponentResult::retrieveByDetails(
        [TestComponentResult::TESTCOMPONENT_ID, TestComponentResult::STUDENT_ID],
        [$tc->getId(), $student->getId()],
        TestComponentResult::RECORDED_TS . ' DESC'
    )[0] ?? null;

$result = new TestComponentResult([
    TestComponentResult::ID => null,
    TestComponentResult::SCORE => $_POST['result'],
    TestComponentResult::STUDENT_ID => $_POST['studentId'],
    TestComponentResult::TESTCOMPONENT_ID => $_POST['testComponentId'],
    TestComponentResult::STAFF_ID => Staff::me($auth_user)->getId(),
]);

$result->commit();

// If the old result was put in less than five minutes ago, we'll just overwrite

if (!is_null($oldResult) && strtotime($oldResult->get(TestComponentResult::RECORDED_TS)) + 43200 > time()) {
    echo "Deleting {$oldResult->getId()}";
    TestComponentResult::delete($oldResult->getId());
}

$percent = $test->calculatePercent($student, $subject);

echo ("percent-{$test->getId()}-$studentId:" . $percent . ",");

$grade = $test->calculateGrade($student, $subject);

echo ("grade-{$test->getId()}-$studentId:$grade");