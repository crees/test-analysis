<?php
namespace TestAnalysis;

class TeachingGroup extends DatabaseCollection
{
    public function __construct(array $details)
    {
        $this->id = $details['id'];
        $this->name = $details['name'];
    }
    
    public function getStudents() {
        $studentIds = array_map(function($x) { return $x['StudentId']; }, (new Database())->dosql("SELECT Student_id FROM StudentGroupMembership WHERE TeachingGroup_id = $this->id;")->fetch_array(MYSQLI_ASSOC));
        return array_map(function($x) { return Student::retrieveByDetail('id', $x); }, $studentIds);
    }

    function __destruct()
    {}
}

