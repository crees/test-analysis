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
        echo <<< eof
        <form method="POST">
            <input type="submit" class="form-control" value="Save">
            <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Group</th>

eof;
        if (count($this->tests) < 1) {
            echo "</tr></table></form><div>No tests defined.</div>";
            return;
        }
        foreach ($this->tests as $t) {
            echo "<th>" . $t->getName() . "</th><th>%</th><th>Grade</th>\n";
        }
        echo "</tr>\n</thead>\n";
        
        $firstTabIndex = 0;
        $studentCount = count($this->students);
        
        foreach ($this->students as $s) {
            $firstTabIndex++;
            $tabIndex = $firstTabIndex;
            echo "<tr>\n";
            echo "<th><a href=\"student_individual_scores.php?student=" . $s->getId() . "\">" . $s->getName() . "</a></th>\n";
            echo "<th>" . $s->getTeachingGroup(Subject::retrieveByDetail(Subject::ID, $this->tests[0]->get(Test::SUBJECT_ID))[0]);
            foreach ($this->tests as $t) {
                $result = $t->getResult($s);
                self::makeTextBoxCell("result-" . $t->getId() . "-" . $s->getId(), is_null($result) ? "" : $result->getScore(), $tabIndex);
                if (is_null($result)) {
                    echo "<td>&nbsp;</td><td>&nbsp;</td>";
                } else {
                    echo "<td>" . round($result->getScore() * 100 / $t->get(Test::TOTAL), 0) . "</td>";
                    echo "<td>" . $t->calculateGrade($result) . "</td>";
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
        echo "<td style=\"padding: 0\">";
        echo "<input class=\"form-control border-0 px-1\" type=\"text\" name=\"$name\" value=\"$value\" $tabindex>";
        echo "</td>\n";
    }

    function __destruct()
    {}
}

