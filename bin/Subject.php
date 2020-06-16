<?php
namespace TestAnalysis;

class Subject extends DatabaseCollection
{
    const CODE = 'code';
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID];
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::CODE] = $details[self::CODE];
    }

    public function getTeachingGroups() {
        $arr = (new Database())->dosql("SELECT TeachingGroup_id FROM GroupSubjectMembership WHERE Subject_id = " . $this->getId() . ";")->fetch_all(MYSQLI_ASSOC);
        if (!isset($arr['TeachingGroup_id'])) {
            return [];
        }
        $teachingGroupIds = array_map(function($x) { return $x['TeachingGroup_id']; }, $arr);
        return array_map(function($x) { return TeachingGroup::retrieveByDetail(TeachingGroup::ID, $x); }, $teachingGroupIds);
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
    
    function __destruct()
    {}
}

