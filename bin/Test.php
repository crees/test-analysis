<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    protected $id, $name, $total, $code;
    
    public function __construct(array $details)
    {
        $this->id = $details['id'];
        $this->name = $details['name'];
        $this->total = $details['total'];
    }
    
    function __destruct()
    {}
}

