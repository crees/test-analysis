<?php
namespace TestAnalysis;

class TestSubjectMembership extends DatabaseCollection
{
    const TEST_ID = 'Test_id';
    const SUBJECT_ID = 'Subject_id';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        $this->details[self::SUBJECT_ID] = $details[self::SUBJECT_ID];
        $this->details[self::TEST_ID] = $details[self::TEST_ID];
    }
    
    public function destroy() {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        
        $testId = $this->get(self::TEST_ID);
        $subjectId = $this->get(self::SUBJECT_ID);
        
        self::$db->dosql("DELETE FROM " . explode('\\', static::class)[1] . " WHERE " . self::TEST_ID . " = $testId AND " . self::SUBJECT_ID . " = $subjectId;");
    }
    
    function __destruct()
    {}
}