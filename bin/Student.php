<?php
namespace TestAnalysis;

class Student extends DatabaseCollection
{
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID];
        $this->details[self::NAME] = $details[self::NAME];
    }
    
    public function getTestResults() {
        return TestResult::retrieveByDetail(TestResult::STUDENT_ID, $this->getId());
    }

    function __destruct()
    {}
}

