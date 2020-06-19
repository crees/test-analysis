<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_GET['removeGroup'])) {
    Subject::retrieveByDetail(Subject::ID, $_GET['removeFromSubject'])[0]->removeMember(TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['removeGroup'])[0]);
} elseif (isset($_GET['newsubjectcode'])) {
    if (!empty($_GET['newsubjectname'])) {
        $s = new Subject([
            Subject::NAME => $_GET['newsubjectname'],
            Subject::CODE => $_GET['newsubjectcode'],
        ]);
        $s->commit();
    }
    
    foreach ($_GET as $k => $value) {
        if (!empty($value)) {
            if (str_contains($k, "subject-add-group-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-add-group-", "", $k))[0];
                $subject->addMember(TeachingGroup::retrieveByDetail(TeachingGroup::ID, $value)[0]);
            } elseif (str_contains($k, "subject-baseline-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-baseline-", "", $k))[0];
                $subject->setBaseLine($value);
                $subject->commit();
            }
        }
    }
}
?>
<!doctype html>
<html><head><?php require "../bin/head.php" ?></head>
<body>
<div class="container">
<div class="row"><a href=".." class="button" role="button">Home</a></div>
<form method="get">
<table class="table table-hover">
<thead><tr><th>Subject code</th><th>Subject name</th><th>Baseline source</th><th>Groups (click to remove)</th><th>Add group</th></tr></thead>
<?php

// Let's get the List of baseline subject IDs
$baselines = [];
$newId = 0;
foreach (Baseline::retrieveAll(Baseline::MIS_ASSESSMENT_ID) as $b) {
    if ($b->get(Baseline::MIS_ASSESSMENT_ID) == $newId) {
        continue;
    }
    $newId = $b->get(Baseline::MIS_ASSESSMENT_ID);
    $newName = $b->get(Baseline::NAME);
    $baselines[$newId] = $newName;
}

$orphanedGroups = TeachingGroup::retrieveAll();
foreach (Subject::retrieveAll(Subject::NAME) as $s) {
    $allGroups = TeachingGroup::retrieveAll();
    echo "<tr><td>" . $s->get(Subject::CODE) . "</td><td>" . $s->get(Subject::NAME) . "</td>";
    $names = [];
    foreach ($s->getTeachingGroups() as $g) {
        array_push($names, "<a href=\"?removeGroup=" . $g->getId() . "&removeFromSubject=" . $s->getId() . "\">" . $g->getName() . "</a>");
        unset($allGroups[array_search($g, $allGroups)]);
        if ($o = array_search($g, $orphanedGroups)) {
            unset($orphanedGroups[$o]);
        }
    }
    echo "<td><select name=\"subject-baseline-" . $s->getId() . "\" onchange=\"this.form.submit()\">";
    if (empty($s->get(Subject::BASELINE_ID))) {
        echo "<option value=\"\" selected>No baseline selected</option>";
    }
    foreach ($baselines as $bId => $bName) {
        if ($s->get(Subject::BASELINE_ID) == $bId) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        echo "<option value=\"$bId\" $selected>$bName</option>";
    }
    echo "</select></td>";
    
    echo "<td>" . implode(", ", $names) . "</td>";
    
    echo "<td><select name=\"subject-add-group-" . $s->getId() . "\" onchange=\"this.form.submit()\">";
    echo "<option value=\"\" selected>Add Group to " . $s->getName() . "</option>";
    foreach ($allGroups as $g) {
        echo "<option value=\"" . $g->getId() . "\">" . $g->getName() . "</option>";
    }
    echo "</select></td>";
    echo "</tr>";
}
?>
<tr>
	<td><input class="form-control" type="text" name="newsubjectcode"></td>

	<td><input class="form-control" type="text" name="newsubjectname"></td>

	<td><input class="form-control" type="submit" value="Add subject"></td>
	
	<td>&nbsp;</td>
</tr>
</table>
</form>
<?php
if (!empty($orphanedGroups)) {
    echo "<div>Groups not assigned to any subject:</div>";

    foreach ($orphanedGroups as $g) {
        echo "<div>" . $g->getName() . "</div>";
    }
}
?>
</div>
</body>
</html>