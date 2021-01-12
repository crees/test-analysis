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

if (Config::is_staff($auth_user)) {
    $stp->setPageScore($_POST['pagescore'] ?? null);
}

$stp->setImage(addslashes(base64_decode(explode(',', str_replace(' ', '+', $_POST['img']), 2)[1])));
$stp->commit();

echo "Success!";