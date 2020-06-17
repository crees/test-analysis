<?php
namespace TestAnalysis;

abstract class DatabaseCollection
{
    const ID='id';
    const NAME='name';
    const OPERATOR_MATCH_ALL = 'MATCHALL';
    
    protected static $db = null; 
    
    protected $details;
    
    /**
     * 
     * @param string $detailType
     * @param string $detail
     * @param Database $db
     * @return \TestAnalysis\DatabaseCollection[]
     */
    public static function retrieveByDetail(string $detailType, string $detail, string $orderBy = "") {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        if ($detail == self::OPERATOR_MATCH_ALL) {
            $where = "";
        } else {
            $where = " WHERE $detailType = '$detail'";
        }
        
        if ($orderBy === "") {
            $orderBy = "";
        } else {
            $orderBy = "ORDER BY $orderBy";
        }
        
        $result = $db->dosql("SELECT * FROM " . explode('\\', static::class)[1] . "$where $orderBy;")->fetch_all(MYSQLI_ASSOC);
        
        if (!isset($result[0])) {
            return [];
        }
        
        $ret = [];
        
        foreach ($result as $r) {
            array_push($ret, new static($r));
        }
        
        return $ret;
    }    
    
    public static function retrieveAll(string $orderBy = "") {
        return static::retrieveByDetail("", self::OPERATOR_MATCH_ALL, $orderBy);
    }
    
    public function commit() {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        $updatelist = [];
        
        foreach ($this->details as $key => $detail) {
            if ($key === self::ID) {
                continue;
            }
            if (!is_null($detail)) {
                array_push($updatelist, "$key = \"$detail\"");
            }
        }
        
        $updatelist = implode(",", $updatelist);
        
        if (is_null($this->getId())) {
            // We don't actually want to replace existing items, we just want a new one if ID is null
            $db->dosql("INSERT INTO " . explode('\\', static::class)[1] . " SET $updatelist;");
        } else {
            $db->dosql("INSERT INTO " . explode('\\', static::class)[1] . "(" . implode(",", array_keys($this->details)) . ") VALUES (\"" .
                implode("\",\"", array_values($this->details)) . "\") ON DUPLICATE KEY UPDATE $updatelist;"
                );
        }
    }
    
    public function getId() { return $this->details[self::ID]; }
    public function getName() { return $this->details[self::NAME]; }
    
    public function get(String $element) { return $this->details[$element]; }
    
    public function setName(String $name) { $this->details[self::NAME] = $name; }

    public function __construct()
    {}

    function __destruct()
    {}
}

