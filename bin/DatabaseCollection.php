<?php
namespace TestAnalysis;

abstract class DatabaseCollection
{
    const ID='id';
    const NAME='name';
    const OPERATOR_MATCH_ALL = 'MATCHALL';
    
    protected static $db = null; 
    
    protected $details;
    
    public static function parseBoolean(Array $details, String $key) {
        if (isset($details[$key])) {
            if ($details[$key] != 0) {
                return 1;
            }
        }
        return 0;
    }
    
    /**
     * 
     * @param array $detailType
     * @param array $detail
     * @param Database $db
     * @return \TestAnalysis\DatabaseCollection[]
     */
    public static function retrieveByDetails(array $detailType, array $detail, string $orderBy = "") {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        if ($detail[0] == self::OPERATOR_MATCH_ALL) {
            $where = "";
        } else {
            $where = " WHERE ";
            for ($i = 0; $i < count($detailType); $i++) {
                if ($i > 0) {
                    $where .= " AND ";
                }
                $where .= "{$detailType[$i]} = '{$detail[$i]}'";
            }
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
    
    /**
     *
     * @param string $detailType
     * @param string $detail
     * @param Database $db
     * @return \TestAnalysis\DatabaseCollection[]
     */
    public static function retrieveByDetail(string $detailType, string $detail, string $orderBy = "") {
        return static::retrieveByDetails([$detailType], [$detail], $orderBy);
    }
    
    public static function retrieveAll(string $orderBy = "") {
        return static::retrieveByDetail("", self::OPERATOR_MATCH_ALL, $orderBy);
    }
    
    public static function delete(int $id) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        self::$db->dosql("DELETE FROM " . explode('\\', static::class)[1] . " WHERE id = $id;");
    }
    
    public function commit() {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        $updatelist = [];
        
        $columnkeys = [];
        $columnvalues = [];
        
        foreach ($this->details as $key => $detail) {
            if (!is_null($detail) && strcmp($detail, "") != 0) {
                array_push($columnkeys, $key);
                array_push($columnvalues, $detail);
                if ($key === self::ID && is_null($detail)) {
                    continue;
                }
                array_push($updatelist, "$key = \"$detail\"");
            }
        }
        
        $updatelist = implode(",", $updatelist);
        
        $columnkeys = implode(",", $columnkeys);
        $columnvalues = implode("\",\"", $columnvalues);
        
        if (!isset($this->details[self::ID]) || is_null($this->getId())) {
            // We don't actually want to replace existing items, we just want a new one if ID is null
            $db->dosql("INSERT INTO " . explode('\\', static::class)[1] . " SET $updatelist;");
        } else {
            $db->dosql("INSERT INTO " . explode('\\', static::class)[1] .
                "($columnkeys) VALUES (\"$columnvalues\") ON DUPLICATE KEY UPDATE $updatelist;"
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

