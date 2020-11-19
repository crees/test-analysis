<?php
namespace TestAnalysis;

class ScannedTestPage extends DatabaseCollection
{
    const TEST_ID = 'Test_id';
    const PAGE_NUM = 'page_num';
    const STUDENT_ID = 'Student_id';
    const IMAGEDATA = 'imagedata';
    const ANNOTATIONS = 'annotations';
    const STUDENT_ANNOTATIONS = 'annotations';
    const MINUTES_ALLOWED = 'minutes_allowed';
    /* Hopefully before 2038... */
    const TS_STARTED = 'ts_started';
    const PAGE_SCORE = 'page_score';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        
        foreach ([  self::TEST_ID,
                    self::PAGE_NUM,
                    self::STUDENT_ID,
                    self::IMAGEDATA,
                    self::ANNOTATIONS,
                    self::PAGE_SCORE,
                    self::MINUTES_ALLOWED,
                    self::STUDENT_ANNOTATIONS,
                    self::TS_STARTED,
                ] as $d) {
            if (false && isset($details[$d])) {
                $this->details[$d] = null;
            } else {
                $this->details[$d] = $details[$d] ?? null;
            }
        }
    }
  
    function __destruct()
    {}
}