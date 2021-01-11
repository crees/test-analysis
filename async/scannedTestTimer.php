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

$ts_started = $scannedTest->get(ScannedTest::TS_STARTED);

echo "$scannedTestId:" . intdiv($scannedTest->secondsRemaining(), 60) . ":$ts_started";