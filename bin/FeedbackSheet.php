<?php
namespace TestAnalysis;

class FeedbackSheet extends DatabaseCollection
{
    const TEMPLATEDATA = 'templatedata';
    
    public function __construct(array $details) {
        if (isset($details[self::ID])) {
            $this->details[self::ID] = $details[self::ID];
        }
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::TEMPLATEDATA] = $details[self::TEMPLATEDATA] ?? null;
    }

    /**
     * Returns an array that you can use to get the substitution values
     * for making a yellow sheet
     * 
     * @param Subject $subject
     * @param Test $test
     * @param Student $student
     * @param string $teacher_name
     * @return string[]
     */
    public static function getSubst(Subject $subject, Test $test, Student $student, $teacher_name = '') {
        
        /* Count targets */
        $number_of_targets = 0;
        
        foreach ($test->get(Test::TARGETS) as $target) {
            if (!empty($target)) {
                $number_of_targets++;
            }
        }
        
        /*
         * Now we need to find the first appropriate target, based on Section B marks.
         *
         * So, we divide the total by the number of targets to get the 'marks per shift'
         *
         * We then shift, except the top three are just the top three.  Clear?  Good.
         */
        
        $marks_to_shift = 0;
        
        foreach ($test->getTestComponents() as $c) {
            if ($c->get(TestComponent::INCLUDED_FOR_TARGETS)) {
                $marks_to_shift += $c->get(TestComponent::TOTAL);
            }
        }
        
        $marks_to_shift /= $number_of_targets;

        $results = [];
        $shiftmarks = 0;
        $marksText = [];
        foreach ($test->getTestComponents() as $c) {
            $r = TestComponentResult::retrieveByDetails(
                [TestComponentResult::STUDENT_ID, TestComponentResult::TESTCOMPONENT_ID, TestComponentResult::INACTIVE],
                [$student->getId(), $c->getId(), 0],
                TestComponentResult::RECORDED_TS . ' DESC'
                );
            if (empty($r)) {
                $marksText = [];
                break;
            }
            $r = $r[0];
            array_push($results, $r);
            if ($c->get(TestComponent::INCLUDED_FOR_TARGETS)) {
                $shiftmarks += $r->get(TestComponentResult::SCORE);
            }
            array_push($marksText, "{$c->getName()}: {$r->get(TestComponentResult::SCORE)}");
        }
        
        $marksText = implode(', ', $marksText);
        
        if (empty($marksText)) {
            $targets = ['', '', ''];
        } else {
            $targets = $test->get(Test::TARGETS);
            $numtargets = 3;
            
            while (($shiftmarks = $shiftmarks - $marks_to_shift) >= 0) {
                if ($numtargets >= 1) {
                    $numtargets--;
                    continue;
                }
                array_shift($targets);
            }
        }
            
        $date = date('d/m/Y');
        $test_total = $test->getTotal();
        
        return [
            'NAME'      => $student->getName(),
            'TEACHER'   => $teacher_name,
            'IGR'       => $student->getIgr($subject),
            'CWAG'      => '',
            'PTG'       => '',
            'ASSESSMENT_TITLE' => $test->getName(),
            'GRADE'     => $test->calculateGrade($student, $subject),
            'MARKS'     => $marksText,
            'MARKS_TOTAL' => $test_total,
            'TARGET_1'  => $targets[0],
            'TARGET_2'  => $targets[1],
            'TARGET_3'  => $targets[2],
            'DATE'      => $date,
        ];
    }
    
    function __destruct()
    {}

}

