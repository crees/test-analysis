<?php
namespace TestAnalysis;

class Student extends DatabaseCollection
{
    protected $id, $name, $code;
    
    public function __construct(array $details)
    {
        $this->id = $details['id'];
        $this->name = $details['name'];
    }

    function __destruct()
    {}
}

