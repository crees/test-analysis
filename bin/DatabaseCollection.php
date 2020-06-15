<?php
namespace TestAnalysis;

abstract class DatabaseCollection
{
    protected $id, $name;
    
    /**
     * 
     * @param string $detailType
     * @param string $detail
     * @param Database $db
     * @return DatabaseCollection[]
     */
    public static function retrieveByDetail(string $detailType, string $detail, Database $db = null) {
        if (is_null($db)) {
            $db = new Database();
        }
        
        if (is_null($detailType)) {
            $where = "";
        } else {
            $where = " WHERE $detailType = '$detail'";
        }
        
        $result = $db->dosql("SELECT * FROM " . explode('\\', static::class)[1] . "$where;")->fetch_all(MYSQLI_ASSOC);
        
        if (!isset($result[0])) {
            return null;
        }
        
        $ret = [];
        
        foreach ($result as $r) {
            array_push($ret, new static($r));
        }
        
        return $ret;
    }
    
    
    public static function retrieveAll(Database $db = null) {
        return static::retrieveByDetail(null, null, $db);
    }
    
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }

    public function __construct()
    {}

    function __destruct()
    {}
}

