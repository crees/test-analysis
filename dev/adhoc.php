<?php 

namespace TestAnalysis;

include "../bin/classes.php";

die("This really is for specialised work");

$tests = explode(" ",  "C1 C2 C3 C4a C4b C4c C5 C6a C6b C7 C8 C9 C10 P2 P3 P4 P5a P5b P6 P7");
$totals = explode(",", "50,50,33,50,34,50,50,30,50,50,50,49,50,32,50,50,30,50,50,39");

$tests = array_combine($tests, $totals);

foreach ($tests as $name => $total) {
    $t = new Test([
        Test::CUSTOM_GRADE_BOUNDARIES => 0,
        Test::DEPARTMENT_ID => 3,
        Test::NAME => "L$name",
    ]);
    print_r($t);
    if (isset($_GET['goforit'])) {
        $t->commit();
    }
    $component = new TestComponent([
        TestComponent::NAME => null,
        TestComponent::TEST_ID => $t->getId(),
        TestComponent::TOTAL => $total,
        TestComponent::INCLUDED_IN_GRADE => 1,
        TestComponent::INCLUDED_IN_PERCENT => 1,
    ]);
    print_r($component);
    if (isset($_GET['goforit'])) {
        $component->commit();
    }
}