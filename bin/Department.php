<?php
namespace TestAnalysis;

class Department extends DatabaseCollection
{
    public function __construct(array $details)
    {
        $this->details[self::NAME] = $details[self::NAME];
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        }
    }
    
    function __destruct()
    {}
}

