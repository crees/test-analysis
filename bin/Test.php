<?php
namespace TestAnalysis;

class Test extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    const TOTAL = 'total';
    
    protected $total;
    
    public function __construct(array $details)
    {
        $this->id = $details[self::ID];
        $this->name = $details[self::NAME];
        $this->total = $details[self::TOTAL];
    }
    
    function __destruct()
    {}
}

