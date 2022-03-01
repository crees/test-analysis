<?php
namespace TestAnalysis;

class TestComponentResult extends DatabaseCollection
{
    const STUDENT_ID = 'Student_id';
    const TESTCOMPONENT_ID = 'TestComponent_id';
    const SCORE = 'score';
    const RECORDED_TS = 'recorded_ts';
    const STAFF_ID = 'Staff_id';
    const INACTIVE = 'inactive';
    
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
        $this->details[self::STAFF_ID] = $details[self::STAFF_ID];
        $this->details[self::INACTIVE] = $details[self::INACTIVE] ?? 0;
    }
    
    public function set(String $field, String $value) {
        if ($field == self::INACTIVE) {
            $this->details[$field] = ($value ? 1 : 0);
        } else {
            $this->details[$field] = $value;
        }
    }
    
    public function toggleActive() {
        $this->update_direct(self::INACTIVE, ($this->details[self::INACTIVE] ? 0 : 1));
    }
    
    function __destruct()
    {}
}

