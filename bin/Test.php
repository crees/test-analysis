<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    const CUSTOM_GRADE_BOUNDARIES = 'custom_grade_boundaries';
    const DEPARTMENT_ID = 'Department_id';
    const TARGETS = 'targets';
    
    protected $components;
    
    public function __construct(array $details)
    {
        $this->components = null;
        $this->details[self::ID] = $details[self::ID] ?? null;
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::DEPARTMENT_ID] = $details[self::DEPARTMENT_ID];
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
    
    public function addTestComponent(array $details) {
        $details[TestComponent::TEST_ID] = $this->getId();
        $component = new TestComponent($details);
        $component->commit();
        $this->components = null;
    }
    
    // TODO Get commit() to handle this gracefully.
    public function getTestComponents($invalidate = false) {
        if (is_null($this->components) || $invalidate) {
            $this->components = TestComponent::retrieveByDetail(TestComponent::TEST_ID, $this->getId(), TestComponent::NAME);
        }
        return $this->components;
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
        $total = 0;
        foreach ($this->getTestComponents() as $c) {
            $total += $c->get(TestComponent::TOTAL);
        }
        return $total;
    }
    
    /**
     * Get the *latest* grade for the student
     * 
     * @param Student $student
     * @param Subject $subject
     * @return String grade
     */
    public function calculateGrade(Student $student, Subject $subject) {
        $score = 0;
        
        $resultComponents = $this->getTestComponentResults($student);
        
        foreach ($this->getTestComponents() as $c) {
            if ($c->get(TestComponent::INCLUDED_IN_GRADE)) {
                if (empty($resultComponents[$c->getId()])) {
                    return '';
                } else {
                    $score += $resultComponents[$c->getId()][0]->get(TestComponentResult::SCORE);
                }
            }
        }

        $grade = 0;
        // First, get the grades ordered by highest to lowest
        foreach ($this->getGradeBoundaries($subject) as $g) {
            if ($g->get(GradeBoundary::BOUNDARY) <= $score) {
                $grade = $g->getName();
                break;
            }
        }
        return $grade;
    }
    
    /**
     * Get the *latest* grade for the student
     *
     * @param Student $student
     * @param Subject $subject
     * @return String grade
     */
    public function calculatePercent(Student $student) {
        $score = 0;
        $percentTotal = 0;
        
        $resultComponents = $this->getTestComponentResults($student);

        if (empty($resultComponents)) {
            return '';
        }
        
        foreach ($this->getTestComponents() as $c) {
            if ($c->get(TestComponent::INCLUDED_IN_PERCENT)) {
                $percentTotal += $c->get(TestComponent::TOTAL);
                if (empty($resultComponents[$c->getId()])) {
                    return '';
                } else {
                    $score += $resultComponents[$c->getId()][0]->get(TestComponentResult::SCORE);
                }
            }
        }

        if ($percentTotal == 0) {
            return NAN;
        } else {
            return round($score * 100.0 / $percentTotal, 0);
        }
    }
    
    /**
     * Gets results for each component;
     * [ componentAid => [most recent TestComponentResult, ...],
     *   componentBid => [most recent TestComponentResult, ...], ...
     * ]
     * 
     * @param Student $student
     * @return array
     */
    public function getTestComponentResults(Student $student) {
        if (isset($this->getLabels()["TestResultComponents-{$student->getId()}"])) {
            return $this->getLabels()["TestResultComponents-{$student->getId()}"];
        }   
        $ret = [];
        $results = TestComponentResult::retrieveByDetail(TestComponentResult::STUDENT_ID, $student->getId(), TestComponentResult::RECORDED_TS . ' DESC');
        foreach ($this->getTestComponents() as $c) {
            $resultsForThisComponent = [];
            foreach ($results as $r) {
                if ($r->get(TestComponentResult::TESTCOMPONENT_ID) == $c->getId()) {
                    array_push($resultsForThisComponent, $r);
                }
            }
            $ret[$c->getId()] = $resultsForThisComponent;
        }
        $this->setLabel("TestResultComponents-{$student->getId()}", $ret);
        return $ret;
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
            $total_for_grade = 0;
            foreach ($this->getTestComponents() as $c) {
                if ($c->get(TestComponent::INCLUDED_IN_GRADE)) {
                    $total_for_grade += $c->get(TestComponent::TOTAL);
                }
            }
            foreach (GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, $id_to_use, GradeBoundary::BOUNDARY . ' DESC') as $g) {
                array_push($ret, new GradeBoundary([
                    GradeBoundary::BOUNDARY => round($g->get(GradeBoundary::BOUNDARY) * $total_for_grade / 100.0, 0),
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

