<?php
namespace TestAnalysis;

class View
{
    protected $c, $r;

    /**
     * 
     * @param Test[] $cols Columns- tests
     * @param Student[] $rows Rows- students
     */
    public function __construct(Array $cols, Array $rows)
    {
        $this->c = $cols;
        $this->r = $rows;
    }
    
    public function print() {
        echo <<< eof
        <form method="POST">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">&nbsp</th>

eof;
        foreach ($this->c as $c) {
            echo "<th>" . $c->getName() . "</th>\n";
        }
        echo "</tr>\n</thead>\n";

        foreach ($this->r as $r) {
            echo "<tr>\n";
            echo "<th>" . $r->getName() . "</th>\n";
            foreach ($this->c as $c) {
                $marks = $c->getResult($r);
                echo "<td><input type=\"text\" id=\"result-" . $c->getId . "-" . $r->getId . " value=\"$marks\"></td>\n";
            }
            echo "</tr>\n";
        }
        
        echo <<< eof
            </table>
        </form>

eof;
    }

    function __destruct()
    {}
}

