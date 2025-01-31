<?php
namespace TestAnalysis;

class Subject extends DatabaseCollection
{
    const BASELINE_ID = 'Baseline_id';
    const DEPARTMENT_ID = 'Department_id';
    const FEEDBACKSHEET_ID = 'FeedbackSheet_id';
    
    public function __construct(array $details)
    {
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::DEPARTMENT_ID] = $details[self::DEPARTMENT_ID];
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        }
        $this->details[self::BASELINE_ID] = $details[self::BASELINE_ID] ?? null;
        $this->details[self::FEEDBACKSHEET_ID] = $details[self::FEEDBACKSHEET_ID] ?? null;
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
    
    public function getTests() {
        $tests = [];
        foreach (TestSubjectMembership::retrieveByDetail(TestSubjectMembership::SUBJECT_ID, $this->getId()) as $membership) {
            $test = Test::retrieveByDetail(Test::ID, $membership->get(TestSubjectMembership::TEST_ID))[0];
            $tests[$test->getName()] = $test;
        }
        ksort($tests);
        return $tests;
    }
    
    public function getFeedbackSheetTemplate() {
        $templateId = $this->get(self::FEEDBACKSHEET_ID);
        if (is_null($templateId)) {
            return null;
        }
        return FeedbackSheet::retrieveByDetail(FeedbackSheet::ID, $templateId)[0];
    }
    
    public function setBaseline(int $bId) {
        $this->details[self::BASELINE_ID] = $bId;
    }
    
    public function setDepartmentId(int $dId) {
        $this->details[self::DEPARTMENT_ID] = $dId;
    }
    
    public function setFeedbackSheetId(int $fId) {
        $this->details[self::FEEDBACKSHEET_ID] = $fId;
    }
    
    public function addTest(Test $test) {
        $membership = new TestSubjectMembership(
            [
                TestSubjectMembership::SUBJECT_ID   => $this->getId(),
                TestSubjectMembership::TEST_ID      => $test->getId(),
            ]
        );
        $membership->commit();
    }
    
    public function removeTest(Test $test) {
        foreach (TestSubjectMembership::retrieveByDetail(TestSubjectMembership::TEST_ID, $test->getId()) as $m) {
            if ($m->get(TestSubjectMembership::SUBJECT_ID) == $this->getId()) {
                $m->destroy();
            }
        }
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
	if (!isset($this->gradeBoundaries)) {
            $this->gradeBoundaries = GradeBoundary::retrieveByDetails([GradeBoundary::TEST_ID, GradeBoundary::BOUNDARY_TYPE], [$this->getId(), GradeBoundary::TYPE_SUBJECT], GradeBoundary::BOUNDARY . ' DESC');
	}
	return $this->gradeBoundaries;
    }

    public function calcCwag(Student $s) {
	$num_tests = 0;
	$total_percent = 0;
	foreach ($this->getTests() as $t) {
		$test_grade = $t->calculateGrade($s, $this);
		foreach ($this->getGradeBoundaries() as $b) {
			if ($b->getName() == $test_grade) {
				$num_tests++;
				$total_percent += $b->get(GradeBoundary::BOUNDARY);
				break;
			}
		}
	}
	if ($num_tests == 0) {
		return null;
	}
	$average_percent = $total_percent / $num_tests;
	return $this->calculateGrade($average_percent);
    }
    
    function __destruct()
    {}
}

