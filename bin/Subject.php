<?php
namespace TestAnalysis;

class Subject extends DatabaseCollection
{
    protected $id, $name, $code;
    protected $teachingGroups;
    
    public function __construct(array $details)
    {
        $this->id = $details['id'];
        $this->name = $details['name'];
        $this->code = $details['code'];
        $this->teachingGroups = [];
        foreach (TeachingGroup::retrieveByDetail('Subject', $this->id) as $s) {
            array_push($this->teachingGroups, $s);
        }
    }

    function __destruct()
    {}
}

