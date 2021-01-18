<?php
namespace TestAnalysis;

class Staff extends DatabaseCollection
{
    const ARBOR_ID = 'arbor_id';
    const FIRST_NAME = 'first_name';
    const LAST_NAME = 'last_name';
    const THEME = 'theme';
    const USERNAME = 'username';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID];
        $this->setNames($details[self::FIRST_NAME], $details[self::LAST_NAME]);
        $this->details[self::USERNAME] = $details[self::USERNAME];
        $this->details[self::ARBOR_ID] = $details[self::ARBOR_ID];
        $this->details[self::THEME] = $details[self::THEME] ?? null;
    }
    
    public function setNames(String $first, String $last) {
        $this->details[self::FIRST_NAME] = $first;
        $this->details[self::LAST_NAME] = $last;
    }
    
    public function setTheme(String $theme) {
        $this->details[self::THEME] = $theme;
    }
    
    public function getName() {
        return $this->details[self::FIRST_NAME] . " " . $this->details[self::LAST_NAME];
    }
    
    function __destruct()
    {}
}

