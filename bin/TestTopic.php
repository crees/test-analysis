<?php
namespace TestAnalysis;

/**
 *
 * @author crees@FreeBSD.org
 *
 */
class TestTopic extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::SUBJECT_ID] = $details[self::SUBJECT_ID];
    }
    
    public function setSubjectId(int $tId) {
        $this->details[self::SUBJECT_ID] = $tId;
    }
    
    public function getTests() {
        $ret = [];
        foreach (TestTestTopic::retrieveByDetail(TestTestTopic::TOPIC_ID, $this->getId()) as $ttt) {
            array_push($ret, Test::retrieveByDetail(Test::ID, $ttt->get(TestTestTopic::TEST_ID))[0]);
        }
        return $ret;
    }
    
    public function setName(String $name) { $this->details[self::NAME] = $name; }
    
    function __destruct()
    {}
}