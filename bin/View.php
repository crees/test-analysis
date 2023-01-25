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
    
    /**
     * Make the headings for students.  If you have a two-row header, the first
     * call should be with $spacers equal to 2.
     * 
     * @param boolean $spacers
     */
    public static function makeStudentTableHeading($spacers = false) {
        if ($spacers == false) {
            echo "<th scope=\"col\">Name</th><th>Group</th>";
        } else {
            echo '<th scope="col">&nbsp;</th>';
        }
        foreach (['M/F', 'PPI', 'SEN', 'EAL', 'Ind.', 'CWAG'] as $heading) {
            if ($spacers) {
                echo '<th scope="col" class="px-0">&nbsp;</th>';
            } else {
                echo "<th class=\"px-0\">$heading</th>";
            }
        }
    }
    
    public static function makeStudentTableRow($student, $teaching_group, $subject) {
        echo "<th scope=\"row\" studentId=\"{$student->getId()}\"><a href=\"student_individual_scores.php?student=" . $student->getId() . "\">" . $student->getLastFirstName() . "</a></th>\n";
        echo "<td>";
        echo ($student->getLabel('group') ?? $teaching_group)->getName();
        echo "</td>";
        echo "<td class=\"px-0 text-center\">{$student->get(Student::GENDER)}</td>";
        $ppi = "&nbsp;";
        $senStatus = null;
        $senNeeds = [];
        $eal = false;
        $nativeLanguages = [];
        foreach (Demographic::retrieveByDetail(Demographic::STUDENT_ID, $student->getId()) as $d) {
            switch ($d->get(Demographic::TAG)) {
            case Demographic::TAG_PUPIL_PREMIUM:
                $ppi = '&#128681;';
                break;
            case Demographic::TAG_SEN_STATUS:
                $senStatus = $d->get(Demographic::DETAIL);
                break;
            case Demographic::TAG_SEN_NEED:
                $senNeeds[] = $d->get(Demographic::DETAIL);
                break;
            case Demographic::TAG_NATIVE_LANGUAGES:
                $eal = true;
                $nativeLanguages[] = $d->get(Demographic::DETAIL);
                break;
            default:
                // Going to just ignore extras
                break;
            }
        }
        echo "<td class=\"px-0 text-center\">$ppi</td>";
        if (is_null($senStatus)) {
            echo "<td class=\"px-0 text-center\">&nbsp;</td>";
        } else {
            $needs = implode("\n", $senNeeds);
            $title = "$senStatus\n\n$needs";
            echo "<td class=\"px-0 text-center\" title=\"$title\" style=\"cursor: help;\">{$senStatus[0]}</td>";
        }
        if ($eal) {
            $title = implode(', ', $nativeLanguages);
            echo "<td class=\"px-0 text-center\" title=\"$title\" style=\"cursor: help;\">&#128681;</td>";
        } else {
            echo "<td class=\"px-0 text-center\">&nbsp;</td>";
        }
        $baseline = $student->getShortIndicative($subject);
        echo "<td id=\"baseline-{$student->getId()}\">$baseline</td>";
    }

    function __destruct()
    {}
}

