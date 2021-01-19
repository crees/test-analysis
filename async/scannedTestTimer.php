<?php
namespace TestAnalysis;

require "../bin/classes.php";

if (!isset($_POST['scannedTestId'])) {
    die("Why are you trying to open this?");
}

$scannedTestId = $_POST['scannedTestId'];

if (!Config::is_staff($auth_user)) {
    die("nope");
}

$scannedTest = ScannedTest::retrieveByDetail(ScannedTest::ID, $scannedTestId);

if (!isset($scannedTest[0])) {
    die("Other failure");
}

$scannedTest = $scannedTest[0];

if (isset($_POST['newTime'])) {
    $scannedTest->setTime($_POST['newTime']);
}

if (isset($_POST['resetTimer'])) {
    $scannedTest->resetTimer();
}

if (isset($_POST['student_upload_allowed'])) {
    $scannedTest->setUploadAllowed($_POST['student_upload_allowed']);
}

if (isset($_POST['forceEndTest'])) {
    $scannedTest->expireTimer();
}

// Refetch after changes
$scannedTest = ScannedTest::retrieveByDetail(ScannedTest::ID, $scannedTestId)[0];

$ts_started = $scannedTest->get(ScannedTest::TS_STARTED);

$student_upload_allowed = $scannedTest->get(ScannedTest::STUDENT_UPLOAD_ALLOWED) ? 1 : 0;

echo "$scannedTestId:" . intdiv($scannedTest->secondsRemaining(), 60) . ":$ts_started:$student_upload_allowed";