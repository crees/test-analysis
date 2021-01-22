<?php
namespace TestAnalysis;

require "../bin/classes.php";

die("Comment this out to make it work");

/**
 * This file takes as input csv in the following format:
 * 
 * Top line contains the IDs of the test components to import in order
 * Subsequent lines:
 * StudentId (from Arbor),firstTestResultA,firstTestResultB,secondTestResultA, [...]
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

$testComponents = [];

foreach ($title as $t) {
    if (is_numeric($t)) {
        array_push($testComponents, $t);
    }
}

print_r($testComponents);
    
while (($line = fgetcsv($f)) !== false) {
    $studentId = $line[0];
    $columnIndex = 1;
    $testIndex = 0;
    while ($columnIndex < sizeof($line) && $testIndex < sizeof($testComponents)) {
        $result = $line[$columnIndex++];
        $testComponentId = $testComponents[$testIndex++];
        if (!is_numeric($result)) {
            continue;
        }
        if ($result == 0) {
            continue;
        }
        $result = new TestComponentResult([
            TestComponentResult::ID => null,
            TestComponentResult::TESTCOMPONENT_ID => $testComponentId,
            TestComponentResult::STUDENT_ID => $studentId,
            TestComponentResult::SCORE => $result,
            TestComponentResult::STAFF_ID => $_SESSION['staff']->getId(),
        ]);
        print_r($result);
        //$result->commit();
    }
}