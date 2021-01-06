<?php
namespace TestAnalysis;

class ScannedTestPage extends DatabaseCollection
{
    const SCANNEDTEST_ID = 'ScannedTest_id';
    const TESTCOMPONENT_ID = 'TestComponent_id';
    const PAGE_NUM = 'page_num';
    const STUDENT_ID = 'Student_id';
    const IMAGEDATA = 'imagedata';
    const ANNOTATIONS = 'annotations';
    const STUDENT_ANNOTATIONS = 'annotations';
    const PAGE_SCORE = 'page_score';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        
        foreach ([  self::SCANNEDTEST_ID,
                    self::TESTCOMPONENT_ID,
                    self::PAGE_NUM,
                    self::STUDENT_ID,
                    self::IMAGEDATA,
                    self::ANNOTATIONS,
                    self::PAGE_SCORE,
                    self::STUDENT_ANNOTATIONS,
                ] as $d) {
            $this->details[$d] = $details[$d] ?? null;
        }
    }
    
    public function setImage($img) {
        $this->details[self::IMAGEDATA] = $img;
    }
    
    public function setPageScore($score) {
        $this->details[self::PAGE_SCORE] = $score;
    }
  
    function __destruct()
    {}
}