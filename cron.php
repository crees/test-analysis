<?php
namespace TestAnalysis;

$cron_auth_skip = true;

include "bin/classes.php";

ScannedTestPage::garbageCollect(true);

die("Done");