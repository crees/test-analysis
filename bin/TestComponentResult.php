<?php
namespace TestAnalysis;

class TestComponentResult extends DatabaseCollection
{
    const STUDENT_ID = 'Student_id';
    const TESTCOMPONENT_ID = 'TestComponent_id';
    const SCORE = 'score';
    const RECORDED_TS = 'recorded_ts';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        }
        $this->details[self::STUDENT_ID] = $details[self::STUDENT_ID];
        $this->details[self::TESTCOMPONENT_ID] = $details[self::TESTCOMPONENT_ID];
        $this->details[self::NAME] = null;
        $this->details[self::SCORE] = $details[self::SCORE];
        if (isset($details[self::RECORDED_TS])) {
            $this->details[self::RECORDED_TS] = strtotime($details[self::RECORDED_TS]);
        } else {
            $this->details[self::RECORDED_TS] = null;
        }
    }
    
    function __destruct()
    {}
}
