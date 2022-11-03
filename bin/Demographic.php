<?php
namespace TestAnalysis;

class Demographic extends DatabaseCollection
{
    const STUDENT_ID = 'Student_id';
    const MIS_ID = 'mis_id';
    const TAG = 'tag';
    const DETAIL = 'detail';
    
    /*
     * The order of these is unimportant, but you need to rerun an import
     * if you are going to renumber these.  Why would you though?
     * Also set the limit in the constructor as well.
     */
    const TAG_PUPIL_PREMIUM = 0;
    const TAG_SEN_NEED = 1;
    const TAG_SEN_STATUS = 2;
    const TAG_NATIVE_LANGUAGES = 3;
    const TAG_IN_CARE_STATUS = 4;
    
    public function __construct(array $details)
    {
        $this->details[self::ID] = $details[self::ID] ?? null;
        $this->details[self::STUDENT_ID] = $details[self::STUDENT_ID];
        $this->details[self::MIS_ID] = $details[self::MIS_ID];
        $this->details[self::DETAIL] = $details[self::DETAIL];
        if (($details[self::TAG] ?? -1) < 0 || $details[self::TAG] > 4)
            throw new \ErrorException("Unrecognised tag {$details[self::TAG]}.");
        $this->details[self::TAG] = $details[self::TAG];
    }
    
    function __destruct()
    {}
}

