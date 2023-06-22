<?php
namespace TestAnalysis;

use \Phpml\Regression\LeastSquares;
use Phpml\Metric\Regression;

class Test extends DatabaseCollection
{
    const CUSTOM_GRADE_BOUNDARIES = 'custom_grade_boundaries';
    const DEPARTMENT_ID = 'Department_id';
    const TARGETS = 'targets';

    const REGRESSION_ERROR_INSUFFICENT_STUDENT_NUMBERS = 1;
    const REGRESSION_ERROR_NO_COMPONENTS = 2;
    const REGRESSION_ERROR_INSUFFICIENT_RESULTS = 3;
    const REGRESSION_ERROR_NO_BASELINE = 4;
    
    protected $components;
    protected $subjects;
    protected array $test_regressions;
    protected array $regression_details;
    protected array $regression_error;
    protected array $cached_total;
    protected array $test_results;
    
    public function __construct(array $details)
    {
        $this->components = null;
        $this->subjects = null;
        $this->details[self::ID] = $details[self::ID] ?? null;
        $this->details[self::NAME] = $details[self::NAME];
        $this->details[self::DEPARTMENT_ID] = $details[self::DEPARTMENT_ID];
        $this->details[self::CUSTOM_GRADE_BOUNDARIES] = self::parseBoolean($details, self::CUSTOM_GRADE_BOUNDARIES);
        if (isset($details[self::TARGETS])) {
            if (is_array($details[self::TARGETS])) {
                $this->details[self::TARGETS] = base64_encode(serialize($details[self::TARGETS]));
            } else {
                $this->details[self::TARGETS] = $details[self::TARGETS];
            }
        } else {
            $this->details[self::TARGETS] = '';
        }
        $this->test_regressions = [];
    }
    
    public function addTestComponent(array $details) {
        $details[TestComponent::TEST_ID] = $this->getId();
        $component = new TestComponent($details);
        $component->commit();
        $this->components = null;
    }
    
    // TODO Get commit() to handle this gracefully.
    public function getTestComponents($invalidate = false) {
        if (is_null($this->components) || $invalidate) {
            $this->components = TestComponent::retrieveByDetail(TestComponent::TEST_ID, $this->getId(), TestComponent::NAME);
        }
        return $this->components;
    }
    
    public function get(String $detail) {
        if ($detail == self::TARGETS) {
            $targets = unserialize(base64_decode($this->details[self::TARGETS]));
            return $targets;
        }
        return parent::get($detail);
    }
    
    public function set(String $detail, $value) {
        if ($detail == self::ID) {
            // We're not even going to think about the pain that would cause
            return false;
        }
        if ($detail == self::TARGETS && isset($value[0])) {
            return $this->details[self::TARGETS] = base64_encode(serialize($value));
        }
        return $this->details[$detail] = $value;
    }
    
    public function getSubjects() {
        if (is_null($this->subjects)) {
            $this->subjects = [];
            foreach(TestSubjectMembership::retrieveByDetail(TestSubjectMembership::TEST_ID, $this->getId()) as $m) {
                array_push($this->subjects, Subject::retrieveByDetail(Subject::ID, $m->get(TestSubjectMembership::SUBJECT_ID))[0]);
            }
        }
        return $this->subjects;
    }
    
    public function getTotal(string $type = 'ALL') {
        if (isset($this->cached_total[$type])) {
            return $this->cached_total[$type];
        }
        $total = 0;
        foreach ($this->getTestComponents() as $c) {
            if ($type == 'ALL' || $c->get($type)) {
                $total += $c->get(TestComponent::TOTAL);
            }
        }
        $this->cached_total[$type] = $total;
        return $total;
    }
    
    /**
     * Get the *latest* grade for the student
     * 
     * @param Student $student
     * @param Subject $subject
     * @return String grade
     */
    public function calculateGrade(Student $student, Subject $subject) {
        $score = 0;
        
        $resultComponents = $this->getTestComponentResults($student);
        
        $gradeComponentExists = false;
        foreach ($this->getTestComponents() as $c) {
            if ($c->get(TestComponent::INCLUDED_IN_GRADE)) {
                $gradeComponentExists = true;
                if (empty($resultComponents[$c->getId()])) {
                    return '';
                } else {
                    $score += $resultComponents[$c->getId()][0]->get(TestComponentResult::SCORE);
                }
            }
        }
        
        if (!$gradeComponentExists) {
            return null;
        }

        $grade = 0;
        // First, get the grades ordered by highest to lowest
        foreach ($this->getGradeBoundaries($subject) as $g) {
            if ($g->get(GradeBoundary::BOUNDARY) <= $score) {
                $grade = $g->getName();
                break;
            }
        }
        return $grade;
    }
    
    /**
     * Get the *latest* grade for the student
     *
     * @param Student $student
     * @param Subject $subject
     * @return String grade
     */
    public function calculatePercent(Student $student) {
        $score = 0;
        $percentTotal = 0;
        
        $resultComponents = $this->getTestComponentResults($student);

        if (empty($resultComponents)) {
            return '';
        }
        
        $percentComponentExists = false;
        foreach ($this->getTestComponents() as $c) {
            if ($c->get(TestComponent::INCLUDED_IN_PERCENT)) {
                $percentComponentExists = true;
                $percentTotal += $c->get(TestComponent::TOTAL);
                if (empty($resultComponents[$c->getId()])) {
                    return '';
                } else {
                    $score += $resultComponents[$c->getId()][0]->get(TestComponentResult::SCORE);
                }
            }
        }
        
        if (!$percentComponentExists) {
            return null;
        }

        if ($percentTotal == 0) {
            return NAN;
        } else {
            return round($score * 100.0 / $percentTotal, 0);
        }
    }
    
    /**
     * Gets results for each component;
     * [ componentAid => [most recent TestComponentResult, ...],
     *   componentBid => [most recent TestComponentResult, ...], ...
     * ]
     * 
     * @param Student $student
     * @param bool $includeInactive=false Include inactive results
     * @return array
     */
    public function getTestComponentResults(Student $student, bool $includeInactive = false) {
        if (isset($this->test_results[$student->getId()])) {
            return $this->test_results[$student->getId()];
        }   
        $ret = [];
        $results = TestComponentResult::retrieveByDetails([TestComponentResult::STUDENT_ID, TestComponentResult::INACTIVE], [$student->getId(), 0], TestComponentResult::RECORDED_TS . ' DESC');
        foreach ($this->getTestComponents() as $c) {
            $resultsForThisComponent = [];
            foreach ($results as $r) {
                if ($r->get(TestComponentResult::TESTCOMPONENT_ID) == $c->getId()) {
                    array_push($resultsForThisComponent, $r);
                }
            }
            $ret[$c->getId()] = $resultsForThisComponent;
        }
        $this->test_results[$student->getId()] = $ret;
        return $ret;
    }
    
    public function getGradeBoundaries(Subject $subject, bool $forceForSubject = false) {
        /*
         * XXX
         *
         * If we have custom grade boundaries set, we need to use the ones per subject, which
         * are stored as negative testIds.
         *
         */
        if ($this->details[self::CUSTOM_GRADE_BOUNDARIES] == 1 && $forceForSubject == false) {
            $ret = GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, $this->getId(), GradeBoundary::BOUNDARY . ' DESC');
        } else {
            $id_to_use = -$subject->getId();
            $ret = [];
            $total_for_grade = 0;
            foreach ($this->getTestComponents() as $c) {
                if ($c->get(TestComponent::INCLUDED_IN_GRADE)) {
                    $total_for_grade += $c->get(TestComponent::TOTAL);
                }
            }
            foreach (GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, $id_to_use, GradeBoundary::BOUNDARY . ' DESC') as $g) {
                array_push($ret, new GradeBoundary([
                    GradeBoundary::BOUNDARY => round($g->get(GradeBoundary::BOUNDARY) * $total_for_grade / 100.0, 0),
                    GradeBoundary::ID       => $g->get(GradeBoundary::ID),
                    GradeBoundary::NAME     => $g->get(GradeBoundary::NAME),
                    GradeBoundary::TEST_ID  => $g->get(GradeBoundary::TEST_ID)
                ]));
            }
        }
        return $ret;
    }
    
    public function getTopics() {
        $ret = [];
        foreach (TestTestTopic::retrieveByDetail(TestTestTopic::TEST_ID, $this->getId()) as $ttt) {
            array_push($ret, TestTopic::retrieveByDetail(TestTopic::ID, $ttt->get(TestTestTopic::TESTTOPIC_ID))[0]);
        }
        return $ret;
    }
    
    protected function getRegressionKey(String $group_prefix, Subject $subject) : String {
        
        return "$group_prefix::{$subject->getId()}";
    }
    
    /**
     * Generate a TestRegression object for the group prefix
     * 
     * As long as groups are named in a sane manner, a year group should work
     * 
     * If the population or sample size is insufficient, a nonzero error is given
     * 
     * @param String $group_prefix
     * @return TestRegression|null
     */
    protected function getRegression(String $group_prefix, Subject $subject) : ?TestRegression {
        $regression_key = $this->getRegressionKey($group_prefix, $subject);
        
        if (isset($this->test_regressions[$regression_key])) {
            return $this->test_regressions[$regression_key];
        }
        
        $r = TestRegression::retrieveByDetails([TestRegression::TEST_ID, TestRegression::REGRESSION_KEY],
                    [$this->getId(), $regression_key]);
        if (count($r) > 1) {
            // Something has clearly gone wrong here, more than one regression!
            // Let's just delete and deal with it later
            foreach ($r as $_r) {
                TestRegression::delete($_r->getId());
            }
            die("Something has gone very wrong here-- extraneous TestRegression objects deleted.");
        } else if (count($r) == 1) {
            // Just return it
            $this->test_regressions[$regression_key] = $r[0];
            return $this->test_regressions[$regression_key];
        }
        
        // Do we have a baseline?
        if (is_null($subject->get(Subject::BASELINE_ID))) {
            Config::debug("Test::getRegression: No baseline set for subject {$subject->getName()}");
            $tr = new TestRegression([
                TestRegression::REGRESSION_KEY => $regression_key,
                TestRegression::TEST_ID => $this->getId(),
                TestRegression::REGRESSION_ERROR => self::REGRESSION_ERROR_NO_BASELINE,
            ]);
            $tr->commit();
            
            $this->test_regressions[$regression_key] = $tr;
            
            return $this->test_regressions;
        }
        
        $regressionComponents = [];
        foreach ($this->getTestComponents() as $c) {
            if ($c->get(TestComponent::INCLUDED_IN_REGRESSION)) {
                $regressionComponents[] = $c;
            }
        }
        
        /* Not doing 'included in regressions'
        if (!isset($regressionComponents[0])) {
            $this->regressions[$regression_key] = null;
            $this->regression_error[$regression_key] = self::REGRESSION_ERROR_NO_COMPONENTS;
            return null;
        }
        
        */
        
        $testResult = [];
        
        $studentCount = 0;
        
        Config::debug("Test::getRegression; extracting TeachingGroups and getting results");
        
        foreach ($subject->getTeachingGroups() as $g) {
            if (preg_match("/^$group_prefix/", $g->getName()) == 1) {
                foreach ($g->getStudentIds() as $studentId) {
                    if (!isset($testResult[$studentId])) {
                        $studentCount++;
                        $testResult[$studentId] = 0;
                        foreach ($regressionComponents as $component) {
                            $resultobj = TestComponentResult::retrieveByDetails([TestComponentResult::TESTCOMPONENT_ID, TestComponentResult::STUDENT_ID, TestComponentResult::INACTIVE], [$component->getId(), $studentId, 0], TestComponentResult::RECORDED_TS . ' DESC');
                            if (isset($resultobj[0])) {
                                $testResult[$studentId] += $resultobj[0]->get(TestComponentResult::SCORE);
                            } else {
                                unset($testResult[$studentId]);
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        Config::debug("Test::getRegression; extracting TeachingGroups done");
        
        // Cache these for when we start doing calculations
        $this->setLabel("testResults_$regression_key", $testResult);
        
        if ($studentCount < Config::regression_minimum_cohort_size) {
            Config::debug("Test::getRegression: Number of students in cohort is insufficent");
            $tr = new TestRegression([
                TestRegression::REGRESSION_KEY => $regression_key,
                TestRegression::TEST_ID => $this->getId(),
                TestRegression::REGRESSION_ERROR => self::REGRESSION_ERROR_INSUFFICENT_STUDENT_NUMBERS,
            ]);
            $tr->commit();
            
            $this->test_regressions[$regression_key] = $tr;
            
            return $this->test_regressions;
        }
        
        $num_results = count($testResult);
        if (100 * $num_results / $studentCount < Config::regression_cohort_minimum_percentage) {
            Config::debug("Test::getRegression: Number of results is insufficent; $num_results / $studentCount");
            $tr = new TestRegression([
                TestRegression::REGRESSION_KEY => $regression_key,
                TestRegression::TEST_ID => $this->getId(),
                TestRegression::REGRESSION_ERROR => Test::REGRESSION_ERROR_INSUFFICIENT_RESULTS,
            ]);
            $tr->commit();
            
            $this->test_regressions[$regression_key] = $tr;
                
            return $this->test_regressions[$regression_key];
        }
        
        /*
         * XXX
         * 
         * This is likely a performance hit.  The simplest code is
         * achieved by doing a separate SQL query for each Student;
         * normally baselines are cached, and it would seemingly make sense
         * to use this, but that involves passing the Student around.
         * I think that this is a possible target for performance improvement,
         * especially when there are several regression columns, but before we
         * increase complexity let's see how the performance manages.
         */
        $trainingBaselines = [];
        $trainingResults = [];
        foreach ($testResult as $sId => $_junk) {
            $_junk; // Unused
            $b = Baseline::retrieveByDetails([Baseline::STUDENT_ID, Baseline::MIS_ASSESSMENT_ID], [$sId, $subject->get(Subject::BASELINE_ID)]);
            if (!isset($b[0])) {
                continue;
            }
            $trainingBaselines[] = [intval(preg_filter('/[^0-9]*/','', $b[0]->get(Baseline::GRADE)))];
            $trainingResults[] = $_junk;
        }
        
        $r = new LeastSquares();
        $r->train($trainingBaselines, $trainingResults);
        
        $tr = new TestRegression([
            TestRegression::TEST_ID => $this->getId(),
            TestRegression::REGRESSION_KEY => $regression_key,
            TestRegression::REGRESSION_INTERCEPT => $r->getIntercept(),
            TestRegression::REGRESSION_GRADIENT => $r->getCoefficients()[0],
        ]);
        $tr->commit();
        
        return $tr;
    }
    
    /**
     * Get a regression result for a Student compared with others in this
     * Subject in the groups prefixed with group_prefix
     * 
     * @param String $group_prefix
     * @param Student $student
     * @param Subject $subject
     * @return String
     */
    public function calculateRegression(String $group_prefix, Student $student, Subject $subject) : String {
        
        $tr = $this->getRegression($group_prefix, $subject);
        
        //$results = $this->getLabel("testResults_" . $this->getRegressionKey($group_prefix, $subject));
        
        $results = $this->getTestComponentResults($student);
        
        $tr_error = $tr->get(TestRegression::REGRESSION_ERROR);
        if ($tr_error != 0) {
            switch($tr_error) {
            case self::REGRESSION_ERROR_INSUFFICENT_STUDENT_NUMBERS:
                return '~';
            case self::REGRESSION_ERROR_INSUFFICIENT_RESULTS:
                return '_';
            case self::REGRESSION_ERROR_NO_COMPONENTS:
                return '';
            default:
                throw new \Exception("Test::calculateRegression: Unknown regression error {$this->regression_error}");
            }
        }
        
        foreach ($results as $c) {
            if (empty($c)) {
                return "&nbsp;";
            }
        }
        
        $baseline = $student->getShortIndicative($subject);
        
        if (empty($baseline)) {
            return "_";
        }
        
        $baseline = preg_filter('/[^0-9]*/','', $student->getBaseline($subject)->get(Baseline::GRADE));
        if ($baseline == '' || !is_numeric($baseline)) {
            return '?';
        }
        $predicted = intval($baseline) * $tr->get(TestRegression::REGRESSION_GRADIENT) + $tr->get(TestRegression::REGRESSION_INTERCEPT);
        
        $total = array_sum(array_map(function ($a) { return $a[0]?->get(TestComponentResult::SCORE) ?? 0;}, $results));
        
        $residual = 100 * round($total - $predicted) / $this->getTotal(TestComponent::INCLUDED_IN_REGRESSION);
        
        $retval = '';
        $symbol = '>';
        if ($residual < 0) {
            $symbol = '<';
            $residual = abs($residual);
        }
        
        while ($residual > 0) {
            $retval .= $symbol;
            $residual -= 10;
        }
        
        if ($symbol == '>') {
            $retval = "-$retval";
        } else {
            $retval .= '-';
        }
        
        return $retval;
    }
    
    function __destruct()
    {}
}

