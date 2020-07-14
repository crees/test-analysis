<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_GET['removeGroup'])) {
    Subject::retrieveByDetail(Subject::ID, $_GET['removeFromSubject'])[0]->removeMember(TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['removeGroup'])[0]);
} elseif (isset($_POST['newsubjectcode'])) {
    if (!empty($_POST['newsubjectname'])) {
        if (empty($_POST['newsubjectnumtargets'])) {
            $_POST['newsubjectnumtargets'] = "12";
        }
        (new Subject([
            Subject::NAME => $_POST['newsubjectname'],
            Subject::CODE => $_POST['newsubjectcode'],
            Subject::NUM_TARGETS => $_POST['newsubjectnumtargets'],
        ]))->commit();
    }
    
    foreach ($_POST as $k => $value) {
        if (!empty($value)) {
            if (str_contains($k, "subject-add-group-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-add-group-", "", $k))[0];
                $subject->addMember(TeachingGroup::retrieveByDetail(TeachingGroup::ID, $value)[0]);
            } elseif (str_contains($k, "subject-baseline-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-baseline-", "", $k))[0];
                $subject->setBaseLine($value);
                $subject->commit();
            } elseif(str_contains($k, "subject-numtargets-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-numtargets-", "", $k))[0];
                $subject->setNumTargets($value);
                $subject->commit();
            } elseif (str_contains($k, "subject-code-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-code-", "", $k))[0];
                $subject->setCode($value);
                $subject->commit();
            } elseif (str_contains($k, "subject-name-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-name-", "", $k))[0];
                $subject->setName($value);
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
    <nav class="navbar navbar-expand">
        <!-- Brand -->
        <a class="navbar-brand">Navigation</a>
        
        <!-- Toggler/collapsibe Button -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
        	<span class="navbar-toggler-icon">collapse</span>
        </button>
        
        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
        	<ul class="navbar-nav">
        		<li class="nav-item">
            		<a class="nav-link" href="../">Home</a>
            	</li>
            	<li class="nav-item">
            		<a class="nav-link" href="index.php">Database management</a>
            	</li>
        	</ul>
    	</div>
    </nav>
<form method="post">
<table class="table table-sm table-hover">
<thead><tr><th>Code</th><th>Name</th><th>Targets</th><th>Baseline source</th><th>Groups (click to remove)</th><th>Add group</th></tr></thead>
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
    echo "<tr>";
    echo View::makeTextBoxCell("subject-code-{$s->getId()}", $s->get(Subject::CODE));
    echo View::makeTextBoxCell("subject-name-{$s->getId()}", $s->get(Subject::NAME));
    echo View::makeTextBoxCell("subject-numtargets-{$s->getId()}", $s->get(Subject::NUM_TARGETS));
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
	
	<td><input class="form-control" type="text" name="newsubjectnumtargets"></td>

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