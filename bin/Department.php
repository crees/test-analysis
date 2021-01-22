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
    
    protected static function _staffDepartments($s, $admin) {
        $departments = [];
        if ($s->isGlobalAdmin()) {
            foreach (Department::retrieveAll() as $dept) {
                $departments[$dept->getId()] = $dept;
            }
        } else {
            foreach (StaffDepartmentMembership::retrieveByDetails(
                [StaffDepartmentMembership::STAFF_ID, StaffDepartmentMembership::DEPARTMENT_ADMIN],
                [$s->getId(), $admin])
                as $m) {
                    $deptId = $m->get(StaffDepartmentMembership::DEPARTMENT_ID);
                    $departments[$deptId] = Department::retrieveByDetail(Department::ID, $deptId)[0];
                }
        }
        return $departments;
    }
    
    public static function staffDepartments($s) { return self::_staffDepartments($s, 0); }
    
    public static function staffAdminDepartments($s) { return self::_staffDepartments($s, 1); }
    
    public function addStaff(Staff $staff) {
        $membership = new StaffDepartmentMembership([
                StaffDepartmentMembership::DEPARTMENT_ID   => $this->getId(),
                StaffDepartmentMembership::STAFF_ID        => $staff->getId(),
            ]);
        $membership->commit();
    }
    
    public function removeStaff(Staff $staff) {
        foreach (StaffDepartmentMembership::retrieveByDetails([StaffDepartmentMembership::STAFF_ID, StaffDepartmentMembership::DEPARTMENT_ID], [$staff->getId(), $this->getId()]) as $m) {
            $m->destroy();
        }
    }
    
    public function getStaff() {
        $s = [];
        foreach (StaffDepartmentMembership::retrieveByDetail(StaffDepartmentMembership::DEPARTMENT_ID, $this->getId()) as $membership) {
            array_push($s, Staff::retrieveByDetail(Staff::ID, $membership->get(StaffDepartmentMembership::STAFF_ID))[0]);
        }
        return $s;
    }
    
    protected function _getUsers($isAdmin) {
        $s = [];
        foreach (StaffDepartmentMembership::retrieveByDetails(
                        [StaffDepartmentMembership::DEPARTMENT_ID, StaffDepartmentMembership::DEPARTMENT_ADMIN], 
                        [$this->getId(), $isAdmin])
                as $membership) {
            array_push($s, Staff::retrieveByDetail(Staff::ID, $membership->get(StaffDepartmentMembership::STAFF_ID))[0]);
        }
        return $s;
    }
    
    public function getAdmins() { return $this->_getUsers(1); }
        
    public function getUsers() { return $this->_getUsers(0); }
    
}

