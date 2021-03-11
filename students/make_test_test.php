<?php

namespace TestAnalysis;

require "../bin/classes.php";

if (Config::is_staff($auth_user)) {
    if (isset($_GET['masquerade'])) {
        $auth_user = strtolower($_GET['masquerade']);
        $msq = "?masquerade={$_GET['masquerade']}";
    } else {
        $msq = "";
        header('Location: index.php');
        die();
    }
}

$student = Student::retrieveByDetail(Student::USERNAME, $auth_user);
if (!isset($student[0])) {
    header("Location: index.php$msq");
    die();
}
$student = $student[0];

$existingTest = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$student->getId(), 0]);

if (isset($existingTest[0])) {
    foreach ($existingTest[0]->getPages() as $p) {
        ScannedTestPage::delete($p->getId());
    }
    ScannedTest::delete($existingTest[0]->getId());
} else {
    $pages = [];
    $zip = new \ZipArchive();
    $zip->open(Config::site_docroot . '/img/trialTest.zip');
    $zipcontents = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $n = $zip->getNameIndex($i);
        if (preg_match('/[.]jpe?g$/', $n) == 1) {
            array_push($zipcontents, $n);
        }
    }
    sort($zipcontents);
    foreach ($zipcontents as $name) {
        array_push($pages, $zip->getFromName($name));
    }
    
    $scannedTest = new ScannedTest([
        ScannedTest::TEST_ID => 0,
        ScannedTest::STUDENT_ID => $student->getId(),
        ScannedTest::SUBJECT_ID => 0,
        ScannedTest::MINUTES_ALLOWED => 20,
        ScannedTest::TS_UNLOCKED => 0,
        ScannedTest::STAFF_ID => 0,
        ScannedTest::STUDENT_UPLOAD_ALLOWED => 0,
        ]);
    $scannedTest->commit();
    foreach ($pages as $num => $p) {
        $page = new ScannedTestPage([
            ScannedTestPage::SCANNEDTEST_ID => $scannedTest->getId(),
            ScannedTestPage::TESTCOMPONENT_ID => 0,
            ScannedTestPage::PAGE_NUM => $num,
        ]);
        $page->setImageData($p);
    }
    $scannedTest->startTimer();
    header("Location: index.php$msq");
    die();
}

header("Location: index.php$msq");
die();