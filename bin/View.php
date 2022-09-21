<?php
namespace TestAnalysis;

abstract class View
{
    private function __construct(Array $cols, Array $rows)
    {}
    
    public static function makeTextBoxCell(String $name, $value, int $tabindex = 0, string $type = "text", string $extras = "", string $tdextras = "") {
        if (is_null($value)) {
            $value = "";
        }
        if ($tabindex != 0) {
            $tabindex = "tabindex=\"$tabindex\"";
        }
        $ret = "";
        $ret .= "<td style=\"padding: 0\" $tdextras>";
        if ($type == 'textarea') {
            $ret .= "<textarea class=\"form-control border-0 px-1\" name=\"$name\" id=\"$name\" $tabindex $extras>$value</textarea>";
        } else {
            $ret .= "<input type=\"$type\" class=\"form-control border-0 px-1\" name=\"$name\" id=\"$name\" value=\"$value\" $tabindex $extras>";
        }
        $ret .= "</td>\n";
        return $ret;
    }

    function __destruct()
    {}
}

