<?php
namespace TestAnalysis;

include "../bin/classes.php";

$staff = Staff::me($auth_user);

$departments = $staff->getAdminDepartments(true);

if (isset($_GET['removeGroup'])) {
    Subject::retrieveByDetail(Subject::ID, $_GET['removeFromSubject'])[0]->removeMember(TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['removeGroup'])[0]);
    header('Location: manage_subjects.php');
    die();
} elseif (isset($_POST['newsubjectname'])) {
    if (!empty($_POST['newsubjectname'])) {
        (new Subject([
            Subject::NAME => $_POST['newsubjectname'],
            Subject::DEPARTMENT_ID => $_POST['newsubjectdept'],
        ]))->commit();
    }
    
    foreach ($_POST as $k => $value) {
        if (!empty($value)) {
            if (str_contains($k, "subject-add-group-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-add-group-", "", $k))[0];
                foreach ($value as $gp) {
                    $tGroups = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $gp);
                    if (empty($tGroups)) {
                        break;
                    }
                    $subject->addMember($tGroups[0]);
                }
            } elseif (str_contains($k, "subject-baseline-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-baseline-", "", $k))[0];
                $subject->setBaseLine($value);
                $subject->commit();
            } elseif (str_contains($k, "subject-name-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-name-", "", $k))[0];
                $subject->setName($value);
                $subject->commit();
            } elseif (str_contains($k, "subject-dept-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-dept-", "", $k))[0];
                $subject->setDepartmentId($value);
                $subject->commit();
            } elseif (str_contains($k, "subject-feedbackid-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-feedbackid-", "", $k))[0];
                $subject->setFeedbackSheetId($value);
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
<thead><tr><th>Name</th><th>Department</th><th>Baseline source</th><th>Feedback sheet template</th><th>Groups (click to remove)</th><th>Add group</th><th>Save</th></tr></thead>
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

$feedbackSheetTemplates = [];
foreach (FeedbackSheet::retrieveAll(FeedbackSheet::NAME) as $f) {
    $feedbackSheetTemplates[$f->getId()] = $f->getName();
}

$orphanedGroups = TeachingGroup::retrieveAll(TeachingGroup::NAME);
foreach ($departments as $department) {
    foreach (Subject::retrieveByDetail(Subject::DEPARTMENT_ID, $department->getId(), Subject::NAME) as $s) {
        $allGroups = TeachingGroup::retrieveAll(TeachingGroup::NAME);
        echo "<tr>";
        echo View::makeTextBoxCell("subject-name-{$s->getId()}", $s->get(Subject::NAME), 0, 'text', 'style="width: min-content;"');
        echo "<td>";
        echo "<select name=\"subject-dept-{$s->getid()}\" onchange=\"this.form.submit()\">";
        foreach ($departments as $dept) {
            if ($dept == $department) {
                $selected = "selected";
            } else {
                $selected = "";
            }
            echo "<option value=\"" . $dept->getId() . "\" $selected>" . $dept->getName() . "</option>";
        }
        echo "</select>";
        echo "</td>";
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
 
        echo "<td><select name=\"subject-feedbackid-" . $s->getId() . "\" onchange=\"this.form.submit()\">";
        if (is_null($s->get(Subject::FEEDBACKSHEET_ID))) {
            echo "<option value=\"\" selected>No template selected</option>";
        }
        foreach ($feedbackSheetTemplates as $fId => $fName) {
            if ($s->get(Subject::FEEDBACKSHEET_ID) == $fId) {
                $selected = "selected";
            } else {
                $selected = "";
            }
            echo "<option value=\"$fId\" $selected>$fName</option>";
        }
        echo "</select></td>";
        
        $names = [];
        foreach ($s->getTeachingGroups() as $g) {
            array_push($names, "<a href=\"?removeGroup=" . $g->getId() . "&removeFromSubject=" . $s->getId() . "\">" . $g->getName() . "</a>");
            unset($allGroups[array_search($g, $allGroups)]);
            if (($o = array_search($g, $orphanedGroups)) !== FALSE) {
                unset($orphanedGroups[$o]);
            }
        }
        echo "<td>" . implode(", ", $names) . "</td>";

        echo "<td><select name=\"subject-add-group-" . $s->getId() . "[]\" multiple>";
        echo "<option value=\"\" selected>Add Group to " . $s->getName() . "</option>";
        foreach ($allGroups as $g) {
            echo "<option value=\"" . $g->getId() . "\">" . $g->getName() . "</option>";
        }
        echo "</select></td>";
        
        echo "<td><input type=\"submit\" value=\"Attach selected groups\"></td>";

        echo "</tr>";
    }
}
?>
<tr>
	<td><input class="form-control" type="text" name="newsubjectname"></td>
<?php
        echo "<td>";
        echo "<select name=\"newsubjectdept\">";
        foreach ($departments as $dept) {
            echo "<option value=\"" . $dept->getId() . "\">" . $dept->getName() . "</option>";
        }
        echo "</select>";
        echo "</td>";
?>
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