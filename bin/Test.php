<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    const TOTAL = 'total';
    
    protected $total;
    
    public function __construct(array $details)
    {
        $this->id = $details[self::ID];
        $this->name = $details[self::NAME];
        $this->total = $details[self::TOTAL];
    }
    
    /**
     * Returns empty string if there is no test mark recorded.
     * 
     * @param Student $s
     * @return integer|string
     */
    public function getResult(Student $s) {
        foreach(TestResult::retrieveByDetail(TestResult::STUDENT_ID, $s->getId()) as $r) {
            if ($r->getTestId() == $this->id) {
                return $r->getScore();
            }
        }
        return "";
    }
    
    function __destruct()
    {}
}

