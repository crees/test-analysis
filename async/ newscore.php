<?php
namespace TestAnalysis;

require "../bin/classes.php";

if (!isset($_POST['studentId']) || !isset($_POST['testId'])) {
    die("Why are you trying to open this?");
}
// studentId=" + studentId + "&testId=" + testId + "&a=" + resultA + "&b=" + resultB

$studentId = $_POST['studentId'];

if (!Config::is_staff($auth_user)) {
    die("nope");
}

(new TestResult([
    TestResult::ID => null,
    TestResult::SCORE_A => $_POST['a'],
    TestResult::SCORE_B => $_POST['b'],
    TestResult::STUDENT_ID => $_POST['studentId'],
    TestResult::TEST_ID => $_POST['testId']
]))->commit();
    
