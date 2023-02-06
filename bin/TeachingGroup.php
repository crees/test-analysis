<?php
namespace TestAnalysis;

class TeachingGroup extends DatabaseCollection
{
    const ACADEMIC_YEAR = 'academic_year';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID];
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::ACADEMIC_YEAR] = $details[self::ACADEMIC_YEAR];
    }
    
    public function getStudents() {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        $arr = $db->dosql("SELECT Student_id FROM StudentGroupMembership WHERE TeachingGroup_id = " . $this->getId() . ";")->fetch_all(MYSQLI_ASSOC);
        
        if (!isset($arr[0])) {
            Config::debug("No kids in the group found");
            return [];
        }
        $studentIds = array_map(function($x) { return $x['Student_id']; }, $arr);
        $students = array_map(function($x) { return Student::retrieveByDetail(Student::ID, $x)[0] ?? null; }, $studentIds);
        uasort($students, function($a, $b) { return $a->getLastFirstName() <=> $b->getLastFirstName();});
        return $students;
    }
    
    public function addMember(int $sid) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        $gid = $this->getId();
        
        // TODO Don't delete, update and update timestamp too
        
        $db->dosql("DELETE FROM StudentGroupMembership WHERE Student_id = $sid AND TeachingGroup_id = $gid;");
        $db->dosql("INSERT INTO StudentGroupMembership(Student_id, TeachingGroup_id) VALUES ($sid, $gid);");
    }

    function __destruct()
    {}
}

