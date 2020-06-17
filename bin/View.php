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
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Group</th>

eof;
        foreach ($this->tests as $t) {
            echo "<th>" . $t->getName() . "</th><th>%</th><th>Grade</th>\n";
        }
        echo "</tr>\n</thead>\n";
        
        foreach ($this->students as $s) {
            echo "<tr>\n";
            echo "<th>" . $s->getName() . "</th>\n";
            echo "<th>" . $s->getTeachingGroup(Subject::retrieveByDetail(Subject::ID, $t->get(Test::SUBJECT_ID))[0]);
            foreach ($this->tests as $t) {
                $marks = $t->getResult($s);
                self::makeTextBoxCell("result-" . $t->getId() . "-" . $s->getId(), $marks);
                if ($marks == "") {
                    echo "<td>&nbsp;</td><td>&nbsp;</td>";
                } else {
                    echo "<td>" . round($marks * 100 / $t->get(Test::TOTAL), 0) . "</td><td>&nbsp;</td>"; // TODO grade calculation
                }
            }
            echo "</tr>\n";
        }
        
        $serial = $_SESSION['form_serial'];
        
        echo <<< eof
            </table>
            <input type="hidden" name="form_serial" value="$serial">

        </form>

eof;
    }
    
    static function makeTextBoxCell(String $name, $value) {
        if (is_null($value)) {
            $value = "";
        }
        echo "<td style=\"padding: 0\">";
        echo "<input class=\"form-control border-0 px-1\" type=\"text\" name=\"$name\" value=\"$value\">";
        echo "</td>\n";
    }

    function __destruct()
    {}
}

