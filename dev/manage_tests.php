<?php
namespace TestAnalysis;

include "../bin/classes.php";

$staff = Staff::me($auth_user);

$departments = $staff->getAdminDepartments(true);

if (isset($_POST['newtest-name']) && isset($_POST['form_serial']) && (session_status() != PHP_SESSION_ACTIVE || $_POST['form_serial'] == $_SESSION['form_serial'] - 1)) {
    foreach (Test::retrieveAll() as $t) {
        $tId = $t->getId();
        // Test modifications
        if (isset($_POST[Test::NAME . "-$tId"]) && !empty($_POST[Test::NAME . "-$tId"])) {
            $detail = [];
            $detail[Test::ID] = $tId;
            $detail[Test::NAME] = $_POST[Test::NAME . "-$tId"];
            $detail[Test::DEPARTMENT_ID] = $_POST["test-department-{$t->getid()}"];
            $detail[Test::CUSTOM_GRADE_BOUNDARIES] = $t->get(Test::CUSTOM_GRADE_BOUNDARIES);
            $newTest = new Test($detail);
            
            $newTest->commit();
        }
    }
    
    if (!empty($_POST['newtest-name'])) {
        $newTestDetails = [];
        foreach ($_POST as $k => $v) {
            if (str_contains($k, "newtest-")) {
                $k = str_replace('newtest-', '', $k);
            }
            $newTestDetails[$k] = $v;
        }
        $t = new Test($newTestDetails);
        $t->commit();
        (new TestComponent([
            TestComponent::NAME => '',
            TestComponent::TEST_ID => $t->getId(),
            TestComponent::TOTAL => 0,
            TestComponent::INCLUDED_IN_PERCENT => 1,
            TestComponent::INCLUDED_IN_GRADE => 1,
            TestComponent::INCLUDED_FOR_TARGETS => 1,
        ]))->commit();
    }
    
    // Let's now examine the grade boundaries.  First handle any that have changed
    foreach (GradeBoundary::retrieveByDetail(GradeBoundary::BOUNDARY_TYPE, GradeBoundary::TYPE_SUBJECT) as $b) {
        $bId = $b->getId();
        if (!isset($_POST["GradeBoundary-grade-$bId"])) {
            continue;
        }
        $newGrade = $_POST["GradeBoundary-grade-$bId"];
        $newBoundary = $_POST["GradeBoundary-boundary-$bId"];
        if ($newGrade != $b->get(GradeBoundary::NAME) || $newBoundary != $b->get(GradeBoundary::BOUNDARY)) {
            $b->setName($newGrade);
            $b->setBoundary($newBoundary);
            $b->commit();
        }
    }
    
    // Now we add any new ones we find for each subject.
    foreach ($departments as $dept) {
        foreach (Subject::retrieveByDetail(Subject::DEPARTMENT_ID, $dept->getId()) as $s) {
            $sId = $s->getId();
            for ($i = 1; $i < 20; $i++) {
                $newGrade = $_POST["GradeBoundary-grade-new-for-subject-$sId-$i"];
                $newBoundary = $_POST["GradeBoundary-boundary-new-for-subject-$sId-$i"];
                if (($newGrade !== "") && ($newBoundary !== "")) {
                    $b = new GradeBoundary([
                        GradeBoundary::NAME => $newGrade,
                        GradeBoundary::BOUNDARY => $newBoundary,
			GradeBoundary::TEST_ID => $sId,
			GradeBoundary::BOUNDARY_TYPE => GradeBoundary::TYPE_SUBJECT
                    ]);
                    $b->commit();
                }
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
<table class="table table-hover table-bordered table-sm">
<thead>
	<tr>
		<th>Department</th>
		<th>Test name</th>
		<th>Edit test details</th>
		<th>Components and totals</th>
	</tr>
</thead>

<tr>
	<?php
	echo "<td><select name=\"newtest-" . Test::DEPARTMENT_ID . "\">";
	foreach ($departments as $dept) {
	    echo "<option value=\"" . $dept->getId() . "\">" . $dept->getName() . "</option>";
	}
	echo "</select></td>";
	echo View::makeTextBoxCell("newtest-" . Test::NAME, "");

	?>

</tr>

<?php
foreach ($departments as $department) {
    foreach (Test::retrieveByDetail(Test::DEPARTMENT_ID, $department->getId(), Test::NAME) as $t) {
        $tId = $t->getId();
        echo "<tr>";
        echo "<td>";
        echo "<select name=\"test-department-{$t->getid()}\" onchange=\"this.form.submit()\">";
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
        // Test name
        echo View::makeTextBoxCell(Test::NAME . "-$tId", $t->get(Test::NAME));
        
        echo "<td><a href=\"manage_test.php?test=$tId\">Edit test details</a></td>";
        
        echo "<td>";
        $components = [];
        foreach ($t->getTestComponents() as $c) {
            $n = $c->getName();
            $n = $n ? "$n: " : "";
            array_push($components, "$n{$c->getTotal()}");
        }
        echo implode(", ", $components);
        echo "</td>";
        
        echo "</tr>";
    }
}
?>
</table>
<input type="submit" class="form-control" value="Save">

<div class="row">You can set default grade boundaries for tests in each subject (these are PERCENTAGES):</div>

<?php
// Subject grade boundaries
foreach ($departments as $department) {
    echo "<div class=\"row\"><div class=\"h3\">{$department->getName()}</div></div>";
    foreach (Subject::retrieveByDetail(Subject::DEPARTMENT_ID, $department->getId(), Subject::NAME) as $s) {
        echo "<table class=\"table table-hover table-bordered table-sm\">";
        $gradeArray = [];
        $boundaryArray = [];
        $columns = 0;
        // We'll give whatever's already there + 20 columns; that should be enough!
        foreach (GradeBoundary::retrieveByDetails([GradeBoundary::TEST_ID, GradeBoundary::BOUNDARY_TYPE], [$s->getId(), GradeBoundary::TYPE_SUBJECT], GradeBoundary::BOUNDARY) as $b) {
            array_push($gradeArray, View::makeTextBoxCell("GradeBoundary-grade-" . $b->getId(), $b->get(GradeBoundary::NAME)));
            array_push($boundaryArray, View::makeTextBoxCell("GradeBoundary-boundary-" . $b->getId(), $b->get(GradeBoundary::BOUNDARY)));
            $columns++;
        }
        for ($i = 1; $i < 20; $i++) {
            array_push($gradeArray, View::makeTextBoxCell("GradeBoundary-grade-new-for-subject-". $s->getId() . "-$i", ""));
            array_push($boundaryArray, View::makeTextBoxCell("GradeBoundary-boundary-new-for-subject-". $s->getId() . "-$i", ""));
            $columns++;
        }
        echo "<thead><tr><th>" . $s->getName() . "</th>";
        while ($columns-- != 0) {
            echo "<th class=\"th-sm\">&nbsp;</th>";
        }
        echo "</tr></thead>";
        echo "<tr><th>Grade</th>";
        echo implode("", $gradeArray);
        echo "</tr>";
        echo "<tr><th>Minimum section B %</th>";
        echo implode("", $boundaryArray);
        echo "</tr>";
        echo "</table>";
    }
}

?>
<input type="hidden" name="form_serial" value="<?= $_SESSION['form_serial']; ?>">
</form>
</div>
</body>
</html>
