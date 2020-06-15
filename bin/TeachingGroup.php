<?php
namespace TestAnalysis;

class TeachingGroup extends DatabaseCollection
{
    const SUBJECT_ID = 'Subject_id';
    
    public function __construct(array $details)
    {
        $this->id = $details[self::ID];
        $this->name = $details[self::NAME];
    }
    
    public function getStudents() {
        $studentIds = array_map(function($x) { return $x['Student_id']; }, (new Database())->dosql("SELECT Student_id FROM StudentGroupMembership WHERE TeachingGroup_id = $this->id;")->fetch_array(MYSQLI_ASSOC));
        return array_map(function($x) { return Student::retrieveByDetail(Student::ID, $x); }, $studentIds);
    }

    function __destruct()
    {}
}

