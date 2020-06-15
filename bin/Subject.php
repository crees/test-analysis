<?php
namespace TestAnalysis;

class Subject extends DatabaseCollection
{
    const CODE = 'code';
    
    protected $code;
    
    public function __construct(array $details)
    {
        $this->id = $details[self::ID];
        $this->name = $details[self::NAME];
        $this->code = $details[self::CODE];
    }

    public function getTeachingGroups() {
        return TeachingGroup::retrieveByDetail(TeachingGroup::SUBJECT_ID, $this->id);
    }
    
    public function getTests() {
        return Test::retrieveByDetail(Test::SUBJECT_ID, $this->id);
    }
    
    function __destruct()
    {}
}

