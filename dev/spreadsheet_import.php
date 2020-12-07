<?php
namespace TestAnalysis;

require "../bin/classes.php";

die("Comment this out to make it work");

/**
 * This file takes as input csv in the following format:
 * 
 * Top line contains the IDs of the tests to import in order
 * Subsequent lines:
 * StudentId,firstTestResultA,firstTestResultB,secondTestResultA, [...]
 * 
 * By default it doesn't commit until that line is uncommented.
 * 
 */

if (!isset($_FILES['spreadsheet'])) {
    die ("<form method=\"post\" enctype=\"multipart/form-data\">Please upload a csv file: <input type=\"file\" name=\"spreadsheet\"><input type=\"submit\"></form>");
}

$f = fopen($_FILES['spreadsheet']['tmp_name'], 'r');

$title = fgetcsv($f);

echo '<pre>';

$tests = [];

foreach ($title as $t) {
    if (is_numeric($t)) {
        array_push($tests, $t);
    }
}

print_r($tests);
    
while (($line = fgetcsv($f)) !== false) {
    $studentId = $line[0];
    $columnIndex = 1;
    $testIndex = 0;
    while ($columnIndex+1 < sizeof($line) && $testIndex < sizeof($tests)) {
        $resultA = $line[$columnIndex++];
        $resultB = $line[$columnIndex++];
        $testId = $tests[$testIndex++];
        if (!is_numeric($resultA) || !is_numeric($resultB)) {
            continue;
        }
        if ($resultA + $resultB == 0) {
            continue;
        }
        $result = new TestResult([
            TestResult::ID => null,
            TestResult::TEST_ID => $testId,
            TestResult::STUDENT_ID => $studentId,
            TestResult::SCORE_A => $resultA,
            TestResult::SCORE_B => $resultB,
        ]);
        print_r($result);
        //$result->commit();
    }
}