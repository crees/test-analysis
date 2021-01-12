<?php
namespace TestAnalysis;

class ScannedTest extends DatabaseCollection
{
    const TEST_ID = 'Test_id';
    const STUDENT_ID = 'Student_id';
    const SUBJECT_ID = 'Subject_id';
    const MINUTES_ALLOWED = 'minutes_allowed';
    /* Hopefully before 2038... */
    const TS_STARTED = 'ts_started';
    const TS_UNLOCKED = 'ts_unlocked';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        
        foreach ([  self::TEST_ID,
                    self::STUDENT_ID,
                    self::SUBJECT_ID,
                    self::MINUTES_ALLOWED,
                    self::TS_STARTED,
                    self::TS_UNLOCKED,
                ] as $d) {
            $this->details[$d] = $details[$d] ?? null;
        }
    }
    
    function startTimer() {
        if ($this->details[self::TS_STARTED] == null) {
            $this->details[self::TS_STARTED] = time();
            $this->commit();
        }
    }
    
    function setTime(int $newTime) {
        $this->details[self::MINUTES_ALLOWED] = $newTime;
        $this->resetTimer();
    }
    
    function resetTimer() {
        $this->details[self::TS_STARTED] = null;
        $this->commit([self::TS_STARTED]);
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