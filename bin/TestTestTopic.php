<?php
namespace TestAnalysis;

class TestTestTopic extends DatabaseCollection
{
    const TEST_ID = 'Test_id';
    const TESTTOPIC_ID = 'TestTopic_id';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        $this->details[self::TESTTOPIC_ID] = $details[self::TESTTOPIC_ID];
        $this->details[self::TEST_ID] = $details[self::TEST_ID];
    }
    
    public function destroy() {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        
        $testId = $this->get(self::TEST_ID);
        $topicId = $this->get(self::TESTTOPIC_ID);
        
        self::$db->dosql("DELETE FROM " . explode('\\', static::class)[1] . " WHERE " . self::TEST_ID . " = $testId AND " . self::TESTTOPIC_ID . " = $topicId;");
    }
    
    function __destruct()
    {}
}