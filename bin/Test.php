<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    const CUSTOM_GRADE_BOUNDARIES = 'custom_grade_boundaries';
    const TOTAL_A = 'total_a';
    const TOTAL_B = 'total_b';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID] ?? null;
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::TOTAL_A] = $details[self::TOTAL_A];
        $this->details[self::TOTAL_B] = $details[self::TOTAL_B];
        $this->details[self::CUSTOM_GRADE_BOUNDARIES] = self::parseBoolean($details, self::CUSTOM_GRADE_BOUNDARIES);
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
        $grade = 0;
        // First, get the grades ordered by highest to lowest
        foreach ($this->getGradeBoundaries() as $g) {
            if ($g->get(GradeBoundary::BOUNDARY) <= $result->get(TestResult::SCORE_B)) {
                $grade = $g->getName();
                break;
            }
        }
        return $grade;
    }
    
    public function getGradeBoundaries(bool $forceForSubject = false) {
        /*
         * XXX
         *
         * If we have custom grade boundaries set, we need to use the ones per subject, which
         * are stored as negative testIds.
         *
         */
        if ($this->details[self::CUSTOM_GRADE_BOUNDARIES] == 1 && $forceForSubject == false) {
            $ret = GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, $this->getId(), GradeBoundary::BOUNDARY . ' DESC');
        } else {
            $id_to_use = -$this->get(self::SUBJECT_ID);
            $ret = [];
            foreach (GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, $id_to_use, GradeBoundary::BOUNDARY . ' DESC') as $g) {
                array_push($ret, new GradeBoundary([
                    GradeBoundary::BOUNDARY => round($g->get(GradeBoundary::BOUNDARY) * $this->get(self::TOTAL_B) / 100.0, 0),
                    GradeBoundary::ID       => $g->get(GradeBoundary::ID),
                    GradeBoundary::NAME     => $g->get(GradeBoundary::NAME),
                    GradeBoundary::TEST_ID  => $g->get(GradeBoundary::TEST_ID)
                ]));
            }
        }
        return $ret;
    }
    
    function __destruct()
    {}
}

