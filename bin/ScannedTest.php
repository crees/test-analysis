<?php
namespace TestAnalysis;

class ScannedTest extends DatabaseCollection
{
    const TEST_ID = 'Test_id';
    const STUDENT_ID = 'Student_id';
    const MINUTES_ALLOWED = 'minutes_allowed';
    /* Hopefully before 2038... */
    const TS_STARTED = 'ts_started';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        
        foreach ([  self::TEST_ID,
                    self::STUDENT_ID,
                    self::MINUTES_ALLOWED,
                    self::TS_STARTED,
                ] as $d) {
            if (false && isset($details[$d])) {
                $this->details[$d] = null;
            } else {
                $this->details[$d] = $details[$d] ?? null;
            }
        }
    }
    
    function startTimer() {
        if ($this->details[self::TS_STARTED] == null) {
            $this->details[self::TS_STARTED] = time();
            $this->commit();
        }
    }
    
    function secondsRemaining() {
        // Has this test been started?
        if ($this->details[self::TS_STARTED] == null) {
            return $this->get(self::MINUTES_ALLOWED) * 60;
        }
        $remainingseconds = ($this->get(self::TS_STARTED) +
            60 * $this->get(self::MINUTES_ALLOWED) - time());
        if ($remainingseconds < 0) {
            $remainingseconds = 0;
        }
        return $remainingseconds;
    }
  
    function getPages() {
        return ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $this->getId(), ScannedTestPage::PAGE_NUM);
    }
    
    function __destruct()
    {}
}