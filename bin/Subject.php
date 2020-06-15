<?php
namespace TestAnalysis;

class Subject extends DatabaseCollection
{
    protected $id, $name, $code;
    
    public function __construct(array $details)
    {
        $this->id = $details['id'];
        $this->name = $details['name'];
        $this->code = $details['code'];
    }

    public function getTeachingGroups() {
        return TeachingGroup::retrieveByDetail('Subject_id', $this->id);
    }
    
    public function getTests() {
        return Test::retrieveByDetail('Subject_id', $this->id);
    }
    
    function __destruct()
    {}
}

