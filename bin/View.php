<?php
namespace TestAnalysis;

abstract class View
{
    private function __construct(Array $cols, Array $rows)
    {}
    
    public static function makeTextBoxCell(String $name, $value, int $tabindex = 0) {
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

