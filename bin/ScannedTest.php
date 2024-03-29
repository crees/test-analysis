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
    const STAFF_ID = 'Staff_id';
    const STUDENT_UPLOAD_ALLOWED = 'student_upload_allowed';
    const DOWNLOADED = 'downloaded';
    
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
                    self::STAFF_ID,
                    self::DOWNLOADED,
                ] as $d) {
            $this->details[$d] = $details[$d] ?? null;
        }
        
        $this->details[self::STUDENT_UPLOAD_ALLOWED] = self::parseBoolean($details, self::STUDENT_UPLOAD_ALLOWED);
    }
    
    function startTimer() {
        if ($this->details[self::TS_STARTED] == null) {
            $this->details[self::TS_STARTED] = time();
            $this->commit();
        }
    }
    
    function setStudentId(int $id) {
        $this->details[self::STUDENT_ID] = $id;
    }
    
    function markAsDownloaded() {
        $this->details[self::DOWNLOADED] = 1;
    }
    
    function setTime(int $newTime) {
        $this->details[self::MINUTES_ALLOWED] = $newTime;
        $this->resetTimer();
    }
    
    function resetTimer() {
        $this->details[self::TS_STARTED] = null;
        $this->commit([self::TS_STARTED]);
    }
    
    function expireTimer() {
        $this->details[self::TS_STARTED] = 0;
        $this->commit();
    }
    
    function setUploadAllowed($uploadAllowed) {
        $this->details[self::STUDENT_UPLOAD_ALLOWED] = $uploadAllowed ? 1 : 0;
        $this->commit();
    }
    
    function secondsRemaining() {
        // Has this test been started?
        if ($this->details[self::TS_STARTED] == null) {
            return $this->get(self::MINUTES_ALLOWED) * 60;
        }
        $remainingseconds = ($this->details[self::TS_STARTED] +
            60 * $this->get(self::MINUTES_ALLOWED) - time());
        if ($remainingseconds < 0) {
            $remainingseconds = 0;
        }
        return $remainingseconds;
    }
  
    function getPages() {
        return ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $this->getId(), ScannedTestPage::PAGE_NUM);
    }
    
    /**
     * 
     * Remove any ScannedTests with no existing pages
     * 
     */
    public static function garbageCollect() {
        self::lock();
        self::$db->dosql("DELETE `ScannedTest` FROM `ScannedTest` WHERE NOT EXISTS (SELECT id FROM `ScannedTestPage` WHERE `ScannedTest`.`id` = `ScannedTestPage`.`ScannedTest_id`);");
        self::unlock();
    }
    
    function __destruct()
    {}
}