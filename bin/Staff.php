<?php
namespace TestAnalysis;

class Staff extends DatabaseCollection
{
    const ARBOR_ID = 'arbor_id';
    const FIRST_NAME = 'first_name';
    const LAST_NAME = 'last_name';
    const THEME = 'theme';
    const USERNAME = 'username';
    const GLOBAL_ADMIN = 'global_admin';
    
    const ADMIN_TYPE_GLOBAL = 'global_admin';
    const ADMIN_TYPE_DEPARTMENT = 'department_admin';
    protected static $me = null;
    protected $admin_type = null;
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID];
        $this->setNames($details[self::FIRST_NAME], $details[self::LAST_NAME]);
        $this->details[self::USERNAME] = $details[self::USERNAME];
        $this->details[self::ARBOR_ID] = $details[self::ARBOR_ID];
        $this->details[self::THEME] = $details[self::THEME] ?? null;
        $this->details[self::GLOBAL_ADMIN] = $details[self::GLOBAL_ADMIN] ?? 0;
    }
    
    public static function me(String $username) {
        if (!is_null(self::$me)) {
            return self::$me;
        }
        if (!Config::is_staff($username)) {
            return null;
        }
        $me = self::retrieveByDetail(self::USERNAME, $username);
        if (isset($me[0]) && !isset($me[1])) {
            // No duplicates, excellent
            self::$me = $me[0];
            return self::$me;
        } else {
            throw new \Exception('No staff found or ambiguous');
        }
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
    
    protected function _getDepartments(bool $sort, bool $admin) {
        if ($this->isGlobalAdmin()) {
            if ($sort) {
                return Department::retrieveAll(Department::NAME);
            } else {
                return Department::retrieveAll();
            }
        }
        $depts = [];
        $details = [StaffDepartmentMembership::STAFF_ID];
        $values = [$this->getId()];
        if ($admin) {
            array_push($details, StaffDepartmentMembership::DEPARTMENT_ADMIN);
            array_push($values, 1);
        }
        foreach (StaffDepartmentMembership::retrieveByDetails($details, $values) as $membership) {
            array_push($depts, Department::retrieveByDetail(Department::ID, $membership->get(StaffDepartmentMembership::DEPARTMENT_ID))[0]);
        }
        if ($sort) {
            usort($depts, function ($a, $b) {
                    return strcmp(strtolower($a->getName()), strtolower($b->getName()));
               });
        }
        return $depts;
    }
    
    public function getDepartments($sort = false) {
        return $this->_getDepartments($sort, false);
    }
    
    public function getAdminDepartments($sort = false) {
        return $this->_getDepartments($sort, true);
    }
    
    public function isGlobalAdmin() {
        if (Config::is_forced_admin($this->get(self::USERNAME)) || $this->get(self::GLOBAL_ADMIN) == 1) {
            return true;
        }
        return false;
    }
    
    public function isDepartmentAdmin(Department $department = null) {
        if ($this->isGlobalAdmin()) {
            return true;
        }
        $details = [StaffDepartmentMembership::STAFF_ID, StaffDepartmentMembership::DEPARTMENT_ADMIN];
        $values = [$this->getId(), 1];
        if (!is_null($department)) {
            array_push($details, StaffDepartmentMembership::DEPARTMENT_ID);
            array_push($values, $department->getId());
        }
        return (count(StaffDepartmentMembership::retrieveByDetails($details, $values)) > 0);
    }
    
    public function adminType() {
        if (!is_null($this->admin_type)) {
            return $this->admin_type;
        }
        if ($this->isGlobalAdmin()) {
            $this->admin_type = self::ADMIN_TYPE_GLOBAL;
        } else if ($this->isDepartmentAdmin()) {
            $this->admin_type = self::ADMIN_TYPE_DEPARTMENT;
        } else {
            $this->admin_type = false;
        }
        return $this->admin_type;
    }
    
    public function updateDetails(array $details) {
        $this->details[self::ARBOR_ID] = $details[self::ARBOR_ID];
        $this->setNames($details[self::FIRST_NAME], $details[self::LAST_NAME]);
    }
    
    public function setGlobalAdmin(int $value) {
        $this->details[self::GLOBAL_ADMIN] = $value;
    }
    
    public function commit($setToNull = []) {
        parent::commit($setToNull);
    }
    
    function __destruct()
    {}
}

