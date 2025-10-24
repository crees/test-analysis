<?php
namespace TestAnalysis;

class StudentGroupMembership extends DatabaseCollection
{
    const STUDENT_ID = 'Student_id';
    const TEACHINGGROUP_ID = 'TeachingGroup_id';
    const TOUCHED_TS = 'touched_ts';
    const ACADEMIC_YEAR = 'academic_year';
    
    public function __construct(array $details)
    {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        } else {
            $this->details[self::ID] = null;
        }
        $this->details[self::STUDENT_ID] = $details[self::STUDENT_ID];
        $this->details[self::TEACHINGGROUP_ID] = $details[self::TEACHINGGROUP_ID];
        $this->details[self::TOUCHED_TS] = $details[self::TOUCHED_TS] ?? 0;
	$this->details[self::ACADEMIC_YEAR] = get_current_AY();
    }
    
    public function destroy() {
        if (is_null(self::$db)) {
            self::$db = new Database();
        }
        
        $studentId = $this->get(self::STUDENT_ID);
        $groupId = $this->get(self::TEACHINGGROUP_ID);
        
        self::$db->dosql("DELETE FROM " . explode('\\', static::class)[1] . " WHERE " . self::STUDENT_ID . " = $studentId AND " . self::TEACHINGGROUP_ID . " = $groupId;");
    }
    
    function __destruct()
    {}
}
