<?php
namespace TestAnalysis;

class Student extends DatabaseCollection
{
    public function __construct(array $details)
    {
        $this->id = $details[self::ID];
        $this->name = $details[self::NAME];
    }
    
    public function getTestResults() {
        return TestResult::retrieveByDetail(TestResult::STUDENT_ID, $this->id);
    }

    function __destruct()
    {}
}

