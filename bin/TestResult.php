<?php
namespace TestAnalysis;

class TestResult extends DatabaseCollection
{
    const SCORE = 'score';
    const STUDENT_ID = 'Student_id';
    const TEST_ID = 'Test_id';
    
    protected $score, $testId, $studentId, $code;
    
    public function __construct(array $details)
    {
        $this->id = $details[self::ID];
        $this->name = null;
        $this->score = $details[self::SCORE];
        $this->testId = $details[self::TEST_ID];
        $this->studentId = $details[self::STUDENT_ID];
    }
    
    public function getTest() {
        return Test::retrieveByDetail(Test::ID, $this->testId);
    }
    
    public function getStudent() {
        return Student::retrieveByDetail(Student::ID, $this->studentId);
    }
    
    public function getTestId() {
        return $this->testId;
    }
    
    public function getScore() {
        return $this->score();
    }
    
    function __destruct()
    {}
}

