<?php
namespace TestAnalysis;

class Subject extends DatabaseCollection
{
    const CODE = 'code';
    const BASELINE_ID = 'Baseline_id';
    const NUM_TARGETS = 'num_targets';
    
    public function __construct(array $details)
    {
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::CODE] = $details[self::CODE];
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        }
        $this->details[self::BASELINE_ID] = $details[self::BASELINE_ID] ?? null;
        $this->details[self::NUM_TARGETS] = $details[self::NUM_TARGETS];
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
        $ids = array_map(function($x) { return TeachingGroup::retrieveByDetail(TeachingGroup::ID, $x)[0]; }, $teachingGroupIds);
        sort($ids);
        return $ids;
    }
    
    public function getStudents() {
        $students = [];
        foreach ($this->getTeachingGroups() as $g) {
            foreach ($g->getStudents() as $s) {
                array_push($students, $s);
            }
        }
        return $students;
    }
    
    public function getTests(string $orderBy = "") {
        return Test::retrieveByDetail(Test::SUBJECT_ID, $this->getId(), $orderBy);
    }
    
    public function setBaseline(int $bId) {
        $this->details[self::BASELINE_ID] = $bId;
    }
    
    public function setNumTargets(int $n) {
        $this->details[self::NUM_TARGETS] = $n;
    }
    
    public function setCode(string $code) {
        $this->details[self::CODE] = $code;
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
    
    public function removeMember(TeachingGroup $group) {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        $db = self::$db;
        
        $sid = $this->getId();
        $gid = $group->getId();
        
        $db->dosql("DELETE FROM GroupSubjectMembership WHERE TeachingGroup_Id = $gid AND Subject_id = $sid;");
    }
    
    public function calculateGrade(int $percentage) {
        $grade = 0;
        // First, get the grades ordered by highest to lowest
        foreach ($this->getGradeBoundaries() as $g) {
            if ($g->get(GradeBoundary::BOUNDARY) <= $percentage) {
                $grade = $g->getName();
                break;
            }
        }
        return $grade;
    }
    
    public function getGradeBoundaries() {
        return GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, -$this->getId(), GradeBoundary::BOUNDARY . ' DESC');
    }
    
    function __destruct()
    {}
}

