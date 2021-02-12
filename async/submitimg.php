<?php
namespace TestAnalysis;

$students_allowed = true;

require "../bin/classes.php";

if (!isset($_POST['img']) || !isset($_POST['stpid'])) {
    die("Why are you trying to open this?");
}

$stp = ScannedTestPage::retrieveByDetail(ScannedTestPage::ID, $_POST['stpid']);

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
            die ("Are you trying to write another kid's image?");
        }
} else if (Config::is_staff($auth_user)) {
    $stp->setPageScore($_POST['pagescore'] ?? null);
}

$stp->setImage(addslashes(base64_decode(explode(',', str_replace(' ', '+', $_POST['img']), 2)[1])));
$stp->commit();

echo "Success!";