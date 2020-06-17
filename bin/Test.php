<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    const TOTAL = 'total';
    
    protected $total;
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID];
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::TOTAL] = $details[self::TOTAL];
        if (isset($details[self::SUBJECT_ID])) {
            $this->details[self::SUBJECT_ID]= $details[self::SUBJECT_ID];
        }
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
    
    function __destruct()
    {}
}

