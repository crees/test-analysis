<?php
namespace TestAnalysis;

class TestResult extends DatabaseCollection
{
    protected $score, $testId, $studentId, $code;
    
    public function __construct(array $details)
    {
        $this->id = $details['id'];
        $this->name = null;
        $this->score = $details['score'];
        $this->testId = $details['Test_id'];
        $this->studentId = $details['Student_id'];
    }
    
    public function getTest() {
        return Test::retrieveByDetail('id', $this->testId);
    }
    
    public function getStudent() {
        return Student::retrieveByDetail('id', $this->studentId);
    }
    
    public function getScore() {
        return $this->score();
    }
    
    function __destruct()
    {}
}

