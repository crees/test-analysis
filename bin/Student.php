<?php
namespace TestAnalysis;

class Student extends DatabaseCollection
{
    const FIRST_NAME = 'first_name';
    const LAST_NAME = 'last_name';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID];
        $this->setNames($details[self::FIRST_NAME], $details[self::LAST_NAME]);
    }
    
    public function getTestResults() {
        return TestResult::retrieveByDetail(TestResult::STUDENT_ID, $this->getId());
    }
    
    public function setNames(String $first, String $last) {
        $this->details[self::FIRST_NAME] = $first;
        $this->details[self::LAST_NAME] = $last;
    }
    
    public function getName() {
        return $this->details[self::FIRST_NAME] . " " . $this->details[self::LAST_NAME];
    }
    
    /**
     * Returns the teaching group the student is in, in the context of the Subject
     * 
     * @param Subject $subject
     * @return string
     */
    public function getTeachingGroup(Subject $subject) {
        foreach ($subject->getTeachingGroups() as $g) {
            if (in_array($this, $g->getStudents())) {
                return $g->get(TeachingGroup::NAME);
            }
        }
        return "NOGROUP??";
    }
    
    public function getBaseline(Subject $subject) {
        if (empty($subject->get(Subject::BASELINE_ID))) {
            return "";
        }
        
        // Get all baselines
        $myBaseLines = Baseline::retrieveByDetails([Baseline::STUDENT_ID, Baseline::MIS_ASSESSMENT_ID], [$this->getId(), $subject->get(Subject::BASELINE_ID)]);
        
        // Now, find the baseline for the Subject
        
        return $myBaseLines[0] ?? "";
    }
    
    public function getIgr(Subject $subject) {
        $baseline = $this->getBaseline($subject);
        if ($baseline instanceOf Baseline) {
            return $baseline->getIgr();
        }
        return "";
    }
    
    public function getShortIndicative(Subject $subject) {
        $baseline = $this->getBaseline($subject);
        if ($baseline instanceOf Baseline) {
            return $baseline->getShortIndicative();
        }
        return "";
    }
    
    public function getAverageGrade(Subject $subject) {
        $percentages_b = [];
        foreach ($subject->getTests() as $t) {
            $result = $t->getResult($this);
            if (!is_null($result)) {
                array_push($percentages_b, $result->get(TestResult::SCORE_B) * 100 / $t->get(Test::TOTAL_B));
            }
        }
        if (count($percentages_b) > 0) {
            return $subject->calculateGrade((int) round(array_sum($percentages_b)/count($percentages_b), 0));
        } else {
            return null;
        }
    }
    
    function __destruct()
    {}
}

