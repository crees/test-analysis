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
        $teachingGroupIds = array_map(function($x) { return $x['TeachingGroup_id']; }, (new Database())->dosql("SELECT TeachingGroup_id FROM GroupSubjectMembership WHERE Subject_id = $this->id;")->fetch_array(MYSQLI_ASSOC));
        return array_map(function($x) { return TeachingGroup::retrieveByDetail(TeachingGroup::ID, $x); }, $teachingGroupIds);

    }
    
    public function getTests() {
        return Test::retrieveByDetail(Test::SUBJECT_ID, $this->id);
    }
    
    function __destruct()
    {}
}

