<?php
namespace TestAnalysis;

class TeachingGroup extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID];
        $this->details[self::NAME] = $details[self::NAME];
    }
    
    public function getStudents() {
        $arr = (new Database())->dosql("SELECT Student_id FROM StudentGroupMembership WHERE TeachingGroup_id = " . $this->getId() . ";")->fetch_all(MYSQLI_ASSOC);
        if (!isset($arr['Student_id'])) {
            return [];
        }
        $studentIds = array_map(function($x) { return $x['Student_id']; }, $arr);
        return array_map(function($x) { return Student::retrieveByDetail(Student::ID, $x); }, $studentIds);
    }

    function __destruct()
    {}
}

