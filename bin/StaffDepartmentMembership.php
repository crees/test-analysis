<?php
namespace TestAnalysis;

class StaffDepartmentMembership extends DatabaseCollection
{
    const STAFF_ID = 'Staff_id';
    const DEPARTMENT_ID = 'Department_id';
    const DEPARTMENT_ADMIN = 'department_admin';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        $this->details[self::DEPARTMENT_ID] = $details[self::DEPARTMENT_ID];
        $this->details[self::STAFF_ID] = $details[self::STAFF_ID];
        $this->details[self::DEPARTMENT_ADMIN] = $details[self::DEPARTMENT_ADMIN] ?? 0;
    }
    
    public function toggleAdmin() {
        $this->details[self::DEPARTMENT_ADMIN] = $this->details[self::DEPARTMENT_ADMIN] == 1 ? 0 : 1;
        $this->commit();
    }
    
    public function destroy() {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        
        $staffId = $this->get(self::STAFF_ID);
        $departmentId = $this->get(self::DEPARTMENT_ID);
        
        self::$db->dosql("DELETE FROM " . explode('\\', static::class)[1] . " WHERE " . self::STAFF_ID . " = $staffId AND " . self::DEPARTMENT_ID . " = $departmentId;");
    }
    
    function __destruct()
    {}
}