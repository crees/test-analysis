<?php
namespace TestAnalysis;

class TeachingGroup extends DatabaseCollection
{
    protected $id, $name, $code;
    protected $students;
    
    public function __construct(array $details)
    {
        $this->id = $details['id'];
        $this->name = $details['name'];
        $this->students = [];
        foreach (Student::retrieveByDetail('TeachingGroup', $this->id) as $g) {
            array_push($this->students, $g);
        }
    }

    function __destruct()
    {}
}

