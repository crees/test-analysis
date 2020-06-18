<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    const CUSTOM_GRADE_BOUNDARIES = 'custom_grade_boundaries';
    const TOTAL = 'total';
    const GRADE = 'grade';
    
    protected $total;
    
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
        foreach ($details as $k => $v) {
            if (substr($k, 0, strlen(self::GRADE)) == self::GRADE) {
                $this->details[$k] = $v;
            }
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
     * @return integer|string
     */
    public function getResult(Student $s) {
        $latest = 0;
        $score = "";
        foreach(TestResult::retrieveByDetail(TestResult::STUDENT_ID, $s->getId()) as $r) {
            if ($r->getTestId() == $this->getId()) {
                $ts = $r->get(TestResult::RECORDED_TS);
                if ($ts > $latest) {
                    $latest = $ts;
                    $score = $r->getScore();
                }
            }
        }
        return $score;
    }
    
    public function calculateGrade(TestResult $result) {
        $grade = 9;
        if ($this->details[self::CUSTOM_GRADE_BOUNDARIES] == 1) {
            while ($this->getGradeBoundary($grade) > $result->getScore()) {
                $grade--;
            }
        } else {
            $percent = $result->getScore() * 100 / $this->get(self::TOTAL);
            while (Config::defaultGradePercent[$grade] > $percent) {
                $grade--;
            }
        }
        return $grade;
    }
    
    public function getGradeBoundary(string $grade) {
        if ($grade == "0") {
            return 0;
        }
        $gradeToGet = self::GRADE . $grade;
        return $this->details[$gradeToGet];
    }
    
    function __destruct()
    {}
}

