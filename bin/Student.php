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
    
    /** Records a new test result.  The latest result is read, but all previous are stored */
    public function recordTestResult(Test $test) {
        
    }

    function __destruct()
    {}
}

