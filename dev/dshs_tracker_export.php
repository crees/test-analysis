<?php
namespace TestAnalysis;

if (!empty($_GET['key'])) {
    $auth_key_prefix = "dshs_tracker:";
}

include "../bin/classes.php";

$getvars = [];
foreach (['testcomponentid'] as $g) {
    if (!isset($_GET[$g]) || !is_numeric($_GET[$g])) {
        die("Unset " . $g);
    }
    $getvars[$g] = $_GET[$g];
}

$results = [];

/* Only take the most recent active results */
foreach (TestComponentResult::retrieveByDetails([TestComponentResult::TESTCOMPONENT_ID, TestComponentResult::INACTIVE], [$getvars['testcomponentid'], 0], TestComponentResult::RECORDED_TS . ' DESC') as $r) {
    if (!isset($results[$r->get(TestComponentResult::STUDENT_ID)])) {
        $results[$r->get(TestComponentResult::STUDENT_ID)] = $r->get(TestComponentResult::SCORE);
    }
}

foreach ($results as $student => $score) {
    echo "$student:$score\n";
}