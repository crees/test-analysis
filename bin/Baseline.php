<?php
namespace TestAnalysis;

use Exception;

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
    
    function getIgr() {
        return $this->details[self::GRADE];
    }
    
    function getShortIndicative() {
        $g = $this->get(self::GRADE);
        // Just a grade/double grade
        if (strlen($g) < 2) {
            return $g;
        }
        // This would match 5-5 5-4 for example, double award
        try {
            if (substr($g, 0, 1) >= substr($g, 2, 1)) {
                return $g;
            }
        } catch (Exception $e) {
            /* DO_NADA(); */
        }
        // So, we have therefore got ranges, 55-77 or 5-7
        return explode('-', $g)[0];
    }
    
    function __destruct()
    {}
}

