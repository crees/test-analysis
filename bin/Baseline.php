<?php
namespace TestAnalysis;

class Baseline extends DatabaseCollection
{
    const MIS_ASSESSMENT_ID = 'Mis_assessment_id';
    const STUDENT_ID = 'Student_id';
    const GRADE = 'grade';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID] ?? null;
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::STUDENT_ID] = $details[self::STUDENT_ID];
        $this->details[self::MIS_ASSESSMENT_ID] = $details[self::MIS_ASSESSMENT_ID];
        $this->details[self::GRADE] = $details[self::GRADE];
    }
    
    function __destruct()
    {}
}

