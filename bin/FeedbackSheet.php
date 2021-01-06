<?php
namespace TestAnalysis;

class FeedbackSheet
{
    protected static $smallLogo = null;
    
    protected Subject $subject;
    protected Test $test;
    protected Student $student;
    protected $teacher_name;

    public function __construct(Subject $subject, Test $test, Student $student, $teacher_name = '') {
        $this->subject = $subject;
        $this->test = $test;
        $this->student = $student;
        $this->teacher_name = $teacher_name;
    }

    public function draw(bool $firstPage = false) {
        
        /* Count targets */
        $number_of_targets = 0;
        
        foreach ($this->test->get(Test::TARGETS) as $target) {
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
        
        foreach ($this->test->getTestComponents() as $c) {
            if ($c->get(TestComponent::INCLUDED_FOR_TARGETS)) {
                $marks_to_shift += $c->get(TestComponent::TOTAL);
            }
        }
        
        $marks_to_shift /= $number_of_targets;

        $results = [];
        $shiftmarks = 0;
        $marksText = [];
        foreach ($this->test->getTestComponents() as $c) {
            $r = TestComponentResult::retrieveByDetails(
                [TestComponentResult::STUDENT_ID, TestComponentResult::TESTCOMPONENT_ID],
                [$this->student->getId(), $c->getId()],
                TestComponentResult::RECORDED_TS . ' DESC'
                );
            if (empty($r)) {
                $results = null;
                break;
            }
            $r = $r[0];
            array_push($results, $r);
            if ($c->get(TestComponent::INCLUDED_FOR_TARGETS)) {
                $shiftmarks += $r->get(TestComponentResult::SCORE);
            }
            array_push($marksText, "{$c->getName()}: {$r->get(TestComponentResult::SCORE)}");
        }
        if (is_null($results)) {
            return;
        }
        
        $marksText = implode(', ', $marksText);
        
        $targets = $this->test->get(Test::TARGETS);
        $numtargets = 3;
        
        while (($shiftmarks = $shiftmarks - $marks_to_shift) >= 0) {
            if ($numtargets >= 1) {
                $numtargets--;
                continue;
            }
            array_shift($targets);
        }
        
        $pagebreak = $firstPage ? '' : 'style="page-break-before: always"';
        $date = date('d/m/Y');
        $img = self::getSmallLogo();
        $test_total = $this->test->getTotal();
        
        echo <<< EOF
<div $pagebreak><img src="$img" style="width:30%;" />&nbsp;</div>

<br />

<div><h1>Science Assessment Record & Feedback</h1></div>

<table style="page-break-after: always">
    <colgroup>
        <col style="width:33%;">
        
        <col style="width:33%;">
        
        <col style="width:34%;">
    </colgroup>
    
    <tr>
        <td colspan="2">Name: {$this->student->getName()}</th>
        
        <td>Teacher: {$this->teacher_name}</th>
    </tr>
    
    <tr>
        <td>Indicative grade range: <strong>{$this->student->getIgr($this->subject)}</strong></td>
        
        <td>Currently working at grade: <strong>{$this->student->getAverageGrade($this->subject)}</strong></td>
        
        <td>Personal target grade:</td>
    </tr>
    
    <tr>
        <td colspan="3">&nbsp;</td>
    <tr>
    
    <tr>
        <td colspan="2">Assessment title: <strong>{$this->test->getName()}</strong></td>
        
        <td>Date: $date</td>
    </tr>
    
    <tr>
        <td>Grade achieved: {$this->test->calculateGrade($this->student, $this->subject)}</td>
        
        <td>Marks achieved: $marksText</td>
        
        <td>Marks available: $test_total</td>
    </tr>
    
    <tr>
        <td colspan=3>
            <div>Targets for improvement are:</div>
            
            <ol>
                <li>$targets[0]</li>
                
                <li>$targets[1]</li>
                
                <li>$targets[2]</li>
            </ol>
        </td>
    </tr>
    
    <tr>
        <td colspan="3">Evidence to show that targets have been met (teacher will sign below):</td>
    </tr>
</table>
EOF;
    }
    
    function __destruct()
    {}

    public static function getSmallLogo() {
        if (is_null(self::$smallLogo)) {
            self::$smallLogo = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(Config::site_docroot . "/img/" . Config::site_small_logo));
        }
        return self::$smallLogo;
    }

}

