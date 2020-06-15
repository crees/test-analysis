<?php
namespace TestAnalysis;

class Student extends DatabaseCollection
{
    public function __construct(array $details)
    {
        $this->id = $details['id'];
        $this->name = $details['name'];
    }
    
    public function getTestResults() {
        return TestResult::retrieveByDetail('Student_id', $this->id);
    }

    function __destruct()
    {}
}

