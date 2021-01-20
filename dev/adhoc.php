<?php 

namespace TestAnalysis;

include "../bin/classes.php";

die("This really is for specialised work");

$tests = explode(",",  "P4,P5a,P5b,P6a,P6b,P7a,P7b,P8");
$totals = explode(",", "50,32,50,49,50,39,50,50");

$tests = array_combine($tests, $totals);

foreach ($tests as $name => $total) {
    $t = new Test([
        Test::CUSTOM_GRADE_BOUNDARIES => 0,
        Test::DEPARTMENT_ID => 4,
        Test::NAME => "Ls$name",
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
    $subjMembership = new TestSubjectMembership([
        TestSubjectMembership::SUBJECT_ID => 36,
        TestSubjectMembership::TEST_ID => $t->getId(),
    ]);
    print_r($subjMembership);
    if (isset($_GET['goforit'])) {
        $subjMembership->commit();
    }
}