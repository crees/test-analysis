<?php
namespace TestAnalysis;

/**
 * The "TEST_ID" field is overloaded- negative numbers refer to Subjects, because why not?
 * 
 * @author ReesCM
 *
 */
class GradeBoundary extends DatabaseCollection
{
    const TEST_ID = 'Test_id';
    const BOUNDARY = 'boundary';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::TEST_ID] = $details[self::TEST_ID];
        $this->details[self::BOUNDARY] = $details[self::BOUNDARY];
    }
    
    public function getTest() {
        return Test::retrieveByDetail(Test::ID, $this->get(self::TEST_ID));
    }
    
    public function setTestId(int $tId) {
        $this->details[self::TEST_ID] = $tId;
    }
    
    public function setName(String $name) { $this->details[self::NAME] = $name; }
    public function setBoundary(int $boundary) { $this->details[self::BOUNDARY] = $boundary; }
    
    function __destruct()
    {}
}