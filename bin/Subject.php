<?php
namespace TestAnalysis;

class Subject extends DatabaseCollection
{
    const CODE = 'code';
    
    public function __construct(array $details)
    {
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::CODE] = $details[self::CODE];
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        }
    }

    public function getTeachingGroups() {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
                
        $arr = $db->dosql("SELECT TeachingGroup_id FROM GroupSubjectMembership WHERE Subject_id = " . $this->getId() . ";")->fetch_all(MYSQLI_ASSOC);
        
        if (!isset($arr[0])) {
            return [];
        }
        $teachingGroupIds = array_map(function($x) { return $x['TeachingGroup_id']; }, $arr);
        return array_map(function($x) { return TeachingGroup::retrieveByDetail(TeachingGroup::ID, $x)[0]; }, $teachingGroupIds);
    }
    
    public function getStudents() {
        $students = [];
        foreach ($this->getTeachingGroups() as $g) {
            foreach ($g->getStudents as $s) {
                array_push($students, $s);
            }
        }
        return $students;
    }
    
    public function getTests() {
        return Test::retrieveByDetail(Test::SUBJECT_ID, $this->getId);
    }
    
    public function addMember(TeachingGroup $group) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        $sid = $this->getId();
        $gid = $group->getId();
        
        $db->dosql("DELETE FROM GroupSubjectMembership WHERE TeachingGroup_Id = $gid AND Subject_id = $sid;");
        $db->dosql("INSERT INTO GroupSubjectMembership(Subject_id, TeachingGroup_id) VALUES ($sid, $gid);");
    }
    
    
    function __destruct()
    {}
}

