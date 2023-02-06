<?php
namespace TestAnalysis;

abstract class DatabaseCollection
{
    const ID='id';
    const NAME='name';
    const OPERATOR_MATCH_ALL = 'MATCHALL';
    
    protected static $db = null; 
    
    protected $details;
    protected $tmplabels;
    
    public static function parseBoolean(Array $details, String $key) {
        if (isset($details[$key])) {
            if ($details[$key] != 0) {
                return 1;
            }
        }
        return 0;
    }
    
    protected static function _retrieveByDetails(array $detailType, array $detail, string $orderBy = "", array $selectQuery = [], bool $distinct = false) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        if (empty($selectQuery)) {
            $selections = '*';
        } else {
            $selections = [];
            foreach ($selectQuery as $q) {
                array_push($selections, '`' . $q . '`');
            }
            $selections = implode(',', $selections);
        }
        
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
        
        $d = $distinct ? "DISTINCT" : "";

        $result = $db->dosql("SELECT $d $selections FROM " . explode('\\', static::class)[1] . "$where $orderBy;")->fetch_all(MYSQLI_ASSOC);

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
     * @param array $detailTypes[]
     * @param array $details[]
     * @param string $orderBy
     * @return \TestAnalysis\DatabaseCollection[]
     */
    public static function retrieveByDetails(array $detailType, array $detail, string $orderBy = "") {
        return static::_retrieveByDetails($detailType, $detail, $orderBy, []);
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
    
    public static function retrieveUniqueValues(string $detailType) {
        $ret = [];
        foreach (static::_retrieveByDetails([""], [self::OPERATOR_MATCH_ALL], $detailType, [$detailType], true) as $c)
            array_push($ret, $c->get($detailType));
            return $ret;
    }
    
    public static function delete(int $id) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        self::$db->dosql("DELETE FROM " . explode('\\', static::class)[1] . " WHERE id = $id;");
    }
    
    public static function lock(bool $readonly = false) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $readwrite = $readonly ? "READ" : "WRITE";
        self::$db->dosql("LOCK TABLES " . explode('\\', static::class)[1] . " $readwrite;");
    }
    
    public static function unlock() {
        if (!is_null(self::$db)) {
            self::$db->dosql("UNLOCK TABLES;");
        }
    }
        
    /**
     * Commits changes-- any that need setting to null should be explicitly
     * listed in the first argument as an array
     * 
     * @param array $setToNull
     */
    public function commit(array $setToNull = []) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        $updatelist = [];
        
        $columnkeys = [];
        $columnvalues = [];
        
        foreach ($this->details as $key => $detail) {
            if ($detail === false) {
                $detail = 0;
            } else if ($detail === true) {
                $detail = 1;
            }
            if (!is_null($detail) && strcmp($detail, "") != 0) {
                array_push($columnkeys, $key);
                array_push($columnvalues, $detail);
                if ($key === self::ID && is_null($detail)) {
                    continue;
                }
                array_push($updatelist, "$key = \"$detail\"");
            }
        }
        
        foreach ($setToNull as $nullKey) {
            $this->details[$nullKey] = null;
            array_push($updatelist, "$nullKey = NULL");
        }
        
        $updatelist = implode(",", $updatelist);
        
        $columnkeys = implode(",", $columnkeys);
        $columnvalues = implode("\",\"", $columnvalues);
        
        if (!isset($this->details[self::ID]) || is_null($this->getId())) {
            // We don't actually want to replace existing items, we just want a new one if ID is null
            $db->dosql("INSERT INTO " . explode('\\', static::class)[1] . " SET $updatelist;");
            $this->details['id'] = $db->dosql("SELECT LAST_INSERT_ID();")->fetch_row()[0];
        } else {
            $db->dosql("INSERT INTO " . explode('\\', static::class)[1] .
                "($columnkeys) VALUES (\"$columnvalues\") ON DUPLICATE KEY UPDATE $updatelist;"
            );
        }
    }
    
    /**
     * Match the first argument's attributes and update any in the second.
     * 
     * Needs to have TOUCHED_TS as a timestamp column, which gets updated
     * when this method is called.
     *  
     * @param array $matching Update if these are matched
     * @param array $update Update these items
     * @param bool $touch Update the updated_ts column.  Don't make this true if that column doesn't exist!
     */
    public static function update_or_create(array $matching, array $update) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $set = [];
        
        foreach ($update as $k => $v) {
            if (is_numeric($v))
                $set[] = "`$k`=$v";
            else 
                $set[] = "`$k`='$v'";
        }
        
        $set[] = "`" . static::TOUCHED_TS . "` = CURRENT_TIMESTAMP";
        
        $where = [];
        
        foreach ($matching as $k => $v) {
            if (is_numeric($v))
                $where[] = "`$k`=$v";
            else
                $where[] = "`$k`='$v'";
        }
        
        $update_cmd = "UPDATE `" . explode('\\', static::class)[1] . "` SET "
            . implode(', ', $set) . " WHERE " . implode(' AND ', $where) . ";";
        self::$db->dosql($update_cmd);

        if (self::$db->get_affected_rows() == 0) {
            $cols = [];
            $values = [];
            foreach (array_merge($matching, $update) as $k => $v) {
                $cols[] = "`$k`";
                if (is_numeric($v))
                    $values[] = "$v";
                else
                    $values[] = "'$v'";
            }
            self::$db->dosql("INSERT INTO `" . explode('\\', static::class)[1] . "` ("
            . implode(', ', $cols) . ") VALUES (" . implode(', ', $values) . ");");
        }
    }
    
    /**
     * Deletes any rows in the table last touched before $age_in_minutes
     * 
     * Requires a timestamped table
     * 
     * @param int $age_in_minutes
     */
    public static function trimBefore(int $age_in_minutes = 60) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        
        if (!defined("static::TOUCHED_TS")) {
            die("Can't use this with a class without touched_ts column; " . static::class);
        }
        
        
        self::$db->dosql("DELETE FROM `" . explode('\\', static::class)[1] . "` WHERE `"
            . static::TOUCHED_TS . "` < NOW() - INTERVAL $age_in_minutes MINUTE;");
    }
    
    protected function update_direct($detail, $value) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        $db->dosql("UPDATE `" . explode('\\', static::class)[1] . "` SET `$detail` = '$value' WHERE `" . self::ID . "` = {$this->getId()};");
    }
    
    public function getId() { return $this->details[self::ID]; }
    public function getName() { return $this->details[self::NAME]; }
    
    public function get(String $element) { return $this->details[$element]; }
    
    public function setName(String $name) { $this->details[self::NAME] = $name; }

    public function setLabel($name, $data) {
        if (empty($this->tmplabels)) {
            $this->tmplabels = [];
        }
        $this->tmplabels[$name] = $data;
    }
    
    public function getLabels() {
        return $this->tmplabels;
    }
    
    public function getLabel($name) {
        return $this->getLabels()[$name] ?? null;
    }
    
    public function __construct()
    {}

    function __destruct()
    {}
}

