<?php
namespace TestAnalysis;

$students_allowed = true;

require "../bin/classes.php";

if (!isset($_GET['stpid'])) {
    die("Why are you trying to open this?");
}

$stp = ScannedTestPage::retrieveByDetail(ScannedTestPage::ID, $_GET['stpid']);

if (!isset($stp[0])) {
    die ("Seriously, stop trying to hack this.");
}

$stp = $stp[0];

if (Config::is_student($auth_user)) {
    $scannedTest = ScannedTest::retrieveByDetail(ScannedTest::ID, $stp->get(ScannedTestPage::SCANNEDTEST_ID));
    // Check that it really is that student
    $student = Student::retrieveByDetail(Student::USERNAME, $auth_user);
    if (!isset($scannedTest[0]) || !isset($student[0]) ||
        $scannedTest[0]->get(ScannedTest::STUDENT_ID) !== $student[0]->getId()) {
        die ("Are you trying to get another kid's image?");
    }    
}

header('Content-Type: image/jpeg');

echo $stp->get(ScannedTestPage::IMAGEDATA);