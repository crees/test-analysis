<?php
namespace TestAnalysis;

class View
{
    protected $tests, $students;

    /**
     * 
     * @param Test[] $cols Columns- tests
     * @param Student[] $rows Rows- students
     */
    public function __construct(Array $cols, Array $rows)
    {
        $this->tests = $cols;
        $this->students = $rows;
    }
    
    public function print() {
        if (count($this->tests) < 1) {
            echo "</tr></table></form><div>No tests defined for selected subject.</div>";
            return;
        }
        echo <<< eof
        <form method="POST">
            <input type="submit" class="form-control" value="Save">
            <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th rowspan="2" scope="col">Name</th>
                        <th rowspan="2" scope="col">Group</th>
                        <th rowspan="2" scope="col">Ind.</th>
eof;
        foreach ($this->tests as $t) {
            echo "<th colspan=\"4\" class=\"text-center\">" . $t->getName() . "</th>\n";
        }
        echo "</tr>\n<tr>";
        
        foreach ($this->tests as $t) {
            echo "<th>A</th><th>B</th><th>%</th><th>Grd</th>\n";
        }
        echo "</tr>\n</thead>\n";
        
        $firstTabIndex = 0;
        $studentCount = count($this->students);
        
        foreach ($this->students as $s) {
            $firstTabIndex++;
            $tabIndex = $firstTabIndex;
            echo "<tr>\n";
            echo "<th><a href=\"student_individual_scores.php?student=" . $s->getId() . "\">" . $s->getName() . "</a></th>\n";
            $subject = Subject::retrieveByDetail(Subject::ID, $this->tests[0]->get(Test::SUBJECT_ID))[0];
            echo "<th>" . $s->getTeachingGroup($subject) . "</th>";
            $baseline = $s->getBaseline($subject);
            echo "<th>$baseline</th>";
            foreach ($this->tests as $t) {
                $result = $t->getResult($s);
                echo self::makeTextBoxCell(TestResult::SCORE_A . "-" . $t->getId() . "-" . $s->getId(), is_null($result) ? "" : $result->get(TestResult::SCORE_A), $tabIndex);
                $tabIndex++;
                echo self::makeTextBoxCell(TestResult::SCORE_B . "-" . $t->getId() . "-" . $s->getId(), is_null($result) ? "" : $result->get(TestResult::SCORE_B), $tabIndex);
                if (is_null($result)) {
                    echo "<td>&nbsp;</td><td>&nbsp;</td>";
                } else {
                    echo "<td>" . round(($result->get(TestResult::SCORE_A)+$result->get(TestResult::SCORE_B)) * 100 / ($t->get(Test::TOTAL_A)+$t->get(Test::TOTAL_B)), 0) . "</td>";
                    $grade = $t->calculateGrade($result);
                    $cellColour = "";
                    if (!empty($baseline)) {
                        if ($grade == $baseline) {
                            $cellColour = "class=\"table-warning\"";
                        } else {
                            foreach ($t->getGradeBoundaries() as $boundary) {
                                if ($baseline == $boundary->getName()) {
                                    $cellColour = "class=\"table-danger\"";
                                    break;
                                }
                                if ($grade == $boundary->getName()) {
                                    // Greater
                                    $cellColour = "class=\"table-success\"";
                                    break;
                                }
                            }
                        }
                    }
                    echo "<td $cellColour>";
                    
                    echo "$grade";
                    
                    echo "</td>";
                }
                $tabIndex += $studentCount;
            }
            echo "</tr>\n";
        }
        
        $serial = $_SESSION['form_serial'];
        
        echo <<< eof
            </table>
            </div>
            <input type="hidden" name="form_serial" value="$serial">

        </form>

eof;
    }
    
    static function makeTextBoxCell(String $name, $value, int $tabindex = 0) {
        if (is_null($value)) {
            $value = "";
        }
        if ($tabindex != 0) {
            $tabindex = "tabindex=\"$tabindex\"";
        }
        $ret = "";
        $ret .= "<td style=\"padding: 0\">";
        $ret .= "<input class=\"form-control border-0 px-1\" type=\"text\" name=\"$name\" value=\"$value\" $tabindex>";
        $ret .= "</td>\n";
        return $ret;
    }

    function __destruct()
    {}
}

