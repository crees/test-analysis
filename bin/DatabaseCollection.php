<?php
namespace TestAnalysis;

abstract class DatabaseCollection
{
    public static function retrieveByDetail(string $detailType, string $detail, Database $db = null) {
        if (is_null($db)) {
            $db = new Database();
        }
        
        $result = $db->dosql("SELECT * FROM " . explode('\\', static::class)[1] . " WHERE $detailType = '$detail';")->fetch_all(MYSQLI_ASSOC);
        
        if (!isset($result[0])) {
            return null;
        }
        
        $ret = [];
        
        foreach ($result as $r) {
            array_push($ret, new static($r));
        }
        
        return $ret;
    }

    public function __construct()
    {}

    function __destruct()
    {}
}

