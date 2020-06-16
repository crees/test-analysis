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
        $this->details[self::ID] = $details[self::ID];
        $this->details[self::NAME] = null;
        $this->details[self::SCORE] = $details[self::SCORE];
        $this->details[self::TEST_ID] = $details[self::TEST_ID];
        $this->details[self::STUDENT_ID] = $details[self::STUDENT_ID];
    }
    
    public function getTest() {
        return Test::retrieveByDetail(Test::ID, $this->get(self::TEST_ID));
    }
    
    public function getStudent() {
        return Student::retrieveByDetail(Student::ID, $this->get(self::STUDENT_ID));
    }
    
    public function getTestId() {
        return $this->get(self::TEST_ID);
    }
    
    public function getScore() {
        return $this->get(self::SCORE);
    }
    
    function __destruct()
    {}
}

