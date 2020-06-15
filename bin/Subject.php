<?php
namespace TestAnalysis;

class Subject extends DatabaseCollection
{
    const CODE = 'code';
    
    protected $code;
    
    public function __construct(array $details)
    {
        $this->id = $details[self::ID];
        $this->name = $details[self::NAME];
        $this->code = $details[self::CODE];
    }

    public function getTeachingGroups() {
        $arr = (new Database())->dosql("SELECT TeachingGroup_id FROM GroupSubjectMembership WHERE Subject_id = $this->id;")->fetch_all(MYSQLI_ASSOC);
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
        return Test::retrieveByDetail(Test::SUBJECT_ID, $this->id);
    }
    
    function __destruct()
    {}
}

