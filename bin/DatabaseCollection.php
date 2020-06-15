<?php
namespace TestAnalysis;

abstract class DatabaseCollection
{
    const ID='id';
    const NAME='name';
    const OPERATOR_MATCH_ALL = 'MATCHALL';
    
    protected $id, $name;
    
    /**
     * 
     * @param string $detailType
     * @param string $detail
     * @param Database $db
     * @return DatabaseCollection[]
     */
    public static function retrieveByDetail(string $detailType, string $detail, string $orderBy = "", Database $db = null) {
        if (is_null($db)) {
            $db = new Database();
        }
        
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
            return null;
        }
        
        $ret = [];
        
        foreach ($result as $r) {
            array_push($ret, new static($r));
        }
        
        return $ret;
    }
    
    
    public static function retrieveAll(string $orderBy = "", Database $db = null) {
        return static::retrieveByDetail("", self::OPERATOR_MATCH_ALL, $orderBy, $db);
    }
    
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }

    public function __construct()
    {}

    function __destruct()
    {}
}

