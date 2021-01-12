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
    if (!isset($scannedTest[0]) || 
        !isset($_SESSION['student_id']) ||
        $scannedTest[0]->get(ScannedTest::STUDENT_ID) !== $_SESSION['student_id']) {
        die ("Are you trying to get another kid's image?");
    }    
}

header('Content-Type: image/jpeg');

echo $stp->get(ScannedTestPage::IMAGEDATA);