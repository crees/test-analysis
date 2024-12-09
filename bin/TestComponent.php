<?php
namespace TestAnalysis;

class TestComponent extends DatabaseCollection
{
    const TEST_ID = 'Test_id';
    const TOTAL = 'total';
    const INCLUDED_IN_PERCENT = 'included_in_percent';
    const INCLUDED_IN_GRADE = 'included_in_grade';
    const INCLUDED_IN_REGRESSION = 'included_in_regression';
    const INCLUDED_FOR_TARGETS = 'included_for_targets';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID] ?? null;
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::TEST_ID] = $details[self::TEST_ID];
        $this->details[self::TOTAL] = $details[self::TOTAL];
        foreach ([self::INCLUDED_FOR_TARGETS, self::INCLUDED_IN_GRADE, self::INCLUDED_IN_PERCENT, self::INCLUDED_IN_REGRESSION] as $inc) {
            $this->details[$inc] = $details[$inc] ?? 0;
        }
    }
    
    public function getTotal() {
        return $this->get(self::TOTAL);
    }
    
    public function set(String $field, String $value) {
        if (str_contains($field, 'included_')) {
            $this->details[$field] = ($value ? 1 : 0);
        } else {
            $this->details[$field] = $value;
        }
    }
    
    function __destruct()
    {}
}

