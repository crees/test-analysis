<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    const CUSTOM_GRADE_BOUNDARIES = 'custom_grade_boundaries';
    const DEPARTMENT_ID = 'Department_id';
    const TOTAL_A = 'total_a';
    const TOTAL_B = 'total_b';
    const TARGETS = 'targets';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID] ?? null;
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::DEPARTMENT_ID] = $details[self::DEPARTMENT_ID];
        $this->details[self::TOTAL_A] = $details[self::TOTAL_A];
        $this->details[self::TOTAL_B] = $details[self::TOTAL_B];
        $this->details[self::CUSTOM_GRADE_BOUNDARIES] = self::parseBoolean($details, self::CUSTOM_GRADE_BOUNDARIES);
        if (isset($details[self::TARGETS])) {
            if (is_array($details[self::TARGETS])) {
                $this->details[self::TARGETS] = base64_encode(serialize($details[self::TARGETS]));
            } else {
                $this->details[self::TARGETS] = $details[self::TARGETS];
            }
        } else {
            $this->details[self::TARGETS] = '';
        }
    }
    
    public function get(String $detail) {
        if ($detail == self::TARGETS) {
            return unserialize(base64_decode($this->details[self::TARGETS]));
        }
        return parent::get($detail);
    }
    
    public function set(String $detail, $value) {
        if ($detail == self::ID) {
            // We're not even going to think about the pain that would cause
            return false;
        }
        if ($detail == self::TARGETS && isset($value[0])) {
            return $this->details[self::TARGETS] = base64_encode(serialize($value));
        }
        return $this->details[$detail] = $value;
    }
    
    public function getSubjects() {
        $s = [];
        foreach(TestSubjectMembership::retrieveByDetail(TestSubjectMembership::TEST_ID, $this->getId()) as $m) {
            array_push($s, Subject::retrieveByDetail(Subject::ID, $m->get(TestSubjectMembership::SUBJECT_ID))[0]);
        }
        return $s;
    }
    
    public function getTotal() {
        return $this->get(self::TOTAL_A) + $this->get(self::TOTAL_B);
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
    
    public function calculateGrade(TestResult $result, Subject $subject) {
        $grade = 0;
        // First, get the grades ordered by highest to lowest
        foreach ($this->getGradeBoundaries($subject) as $g) {
            if ($g->get(GradeBoundary::BOUNDARY) <= $result->get(TestResult::SCORE_B)) {
                $grade = $g->getName();
                break;
            }
        }
        return $grade;
    }
    
    public function getGradeBoundaries(Subject $subject, bool $forceForSubject = false) {
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
            $id_to_use = -$subject->getId();
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
    
    public function getTopics() {
        $ret = [];
        foreach (TestTestTopic::retrieveByDetail(TestTestTopic::TEST_ID, $this->getId()) as $ttt) {
            array_push($ret, TestTopic::retrieveByDetail(TestTopic::ID, $ttt->get(TestTestTopic::TESTTOPIC_ID))[0]);
        }
        return $ret;
    }
    
    function __destruct()
    {}
}

