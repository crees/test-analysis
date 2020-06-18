<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    const CUSTOM_GRADE_BOUNDARIES = 'custom_grade_boundaries';
    const TOTAL = 'total';
    
    public function __construct(array $details)
    {
        if (!isset($details[self::ID])) {
            $this->details[self::ID] = null;
        } else {
            $this->details[self::ID] = $details[self::ID];
        }
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::TOTAL] = $details[self::TOTAL];
        if (isset($details[self::CUSTOM_GRADE_BOUNDARIES]))
            if ($details[self::CUSTOM_GRADE_BOUNDARIES] != 0) {
                $this->details[self::CUSTOM_GRADE_BOUNDARIES] = 1;
            } else {
                $this->details[self::CUSTOM_GRADE_BOUNDARIES] = 0;
        } else {
            $this->details[self::CUSTOM_GRADE_BOUNDARIES] = 0;
        }
        if (isset($details[self::SUBJECT_ID])) {
            $this->details[self::SUBJECT_ID]= $details[self::SUBJECT_ID];
        }
    }
    
    public function set(String $detail, String $value) {
        if ($detail == self::ID) {
            // We're not even going to think about the pain that would cause
            return false;
        }
        return $this->details[$detail] = $value;
    }
    
    /**
     * Returns empty string if there is no test mark recorded.
     * 
     * If multiple test scores recorded, return latest
     * 
     * @param Student $s
     * @return TestResult
     */
    public function getResult(Student $s) {
        $latest = 0;
        $result = null;
        foreach(TestResult::retrieveByDetail(TestResult::STUDENT_ID, $s->getId()) as $r) {
            if ($r->getTestId() == $this->getId()) {
                $ts = $r->get(TestResult::RECORDED_TS);
                if ($ts > $latest) {
                    $latest = $ts;
                    $result = $r;
                }
            }
        }
        return $result;
    }
    
    public function calculateGrade(TestResult $result) {
        /* 
         * XXX
         * 
         * If we have custom grade boundaries set, we need to use the ones per subject, which
         * are stored as negative testIds.
         * 
         */
        if ($this->details[self::CUSTOM_GRADE_BOUNDARIES] == 1) {
            $id_to_use = $this->getId();
            $result_to_use = $result->getScore();
        } else {
            $id_to_use = -$this->get(self::SUBJECT_ID);
            $result_to_use = round($result->getScore() * 100 / $this->get(Test::TOTAL), 0);
        }
        $grade = 0;
        // First, get the grades ordered by highest to lowest
        foreach (GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, $id_to_use, GradeBoundary::BOUNDARY . ' DESC') as $g) {
            if ($g->get(GradeBoundary::BOUNDARY) <= $result_to_use) {
                $grade = $g->getName();
                break;
            }
        }
        return $grade;
    }
    
    function __destruct()
    {}
}

