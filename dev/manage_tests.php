<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_GET['newtest-name'])) {
    foreach (Test::retrieveAll() as $t) {
        $tId = $t->getId();
        if (isset($_GET[Test::SUBJECT_ID . "-$tId"]) && !empty($_GET[Test::SUBJECT_ID . "-$tId"])) {
            $detail = [];
            $detail[Test::ID] = $tId;
            $detail[Test::SUBJECT_ID] = $_GET[Test::SUBJECT_ID . "-$tId"];
            $detail[Test::NAME] = $_GET[Test::NAME . "-$tId"];
            // $t->set(Test::TOPIC, $_GET[Test::TOPIC . "-$tId"]);
            $detail[Test::TOTAL_A] = $_GET[Test::TOTAL_A . "-$tId"];
            $detail[Test::TOTAL_B] = $_GET[Test::TOTAL_B . "-$tId"];
            if (isset($_GET[Test::CUSTOM_GRADE_BOUNDARIES . "-$tId"])) {
                $detail[Test::CUSTOM_GRADE_BOUNDARIES] = 1;
            } else {
                $detail[Test::CUSTOM_GRADE_BOUNDARIES] = 0;
            }
            
            $newTest = new Test($detail);
            
            $newTest->commit();
        }
    }
    
    if (!empty($_GET['newtest-name'])) {
        $newTestDetails = [];
        foreach ($_GET as $k => $v) {
            if (str_contains($k, "newtest-")) {
                $k = str_replace('newtest-', '', $k);
                if ($k == Test::CUSTOM_GRADE_BOUNDARIES) {
                    $newTestDetails[$k] = 1;
                } else {
                    $newTestDetails[$k] = $v;
                }
            }
        }
        $t = new Test($newTestDetails);
        $t->commit();
    }
    
    // Let's now examine the grade boundaries.  First handle any that have changed
    foreach (GradeBoundary::retrieveAll() as $b) {
        $bId = $b->getId();
        if (!isset($_GET["GradeBoundary-grade-$bId"])) {
            continue;
        }
        $newGrade = $_GET["GradeBoundary-grade-$bId"];
        $newBoundary = $_GET["GradeBoundary-boundary-$bId"];
        if ($newGrade != $b->get(GradeBoundary::NAME) || $newBoundary != $b->get(GradeBoundary::BOUNDARY)) {
            $b->setName($newGrade);
            $b->setBoundary($newBoundary);
            $b->commit();
        }
    }
    
    // Now we add any new ones we find for each subject.
    foreach (Subject::retrieveAll() as $s) {
        $sId = $s->getId();
        for ($i = 1; $i < 20; $i++) {
            $newGrade = $_GET["GradeBoundary-grade-new-for-subject-$sId-$i"];
            $newBoundary = $_GET["GradeBoundary-boundary-new-for-subject-$sId-$i"];
            if (!empty($newGrade) && !empty($newBoundary)) {
                $b = new GradeBoundary([
                    GradeBoundary::NAME => $newGrade,
                    GradeBoundary::BOUNDARY => $newBoundary,
                    GradeBoundary::TEST_ID => -$sId,
                ]);
                $b->commit();
            }
        }
    }

    // Finally add any new custom grade boundaries
    foreach (Test::retrieveAll() as $t) {
        $tId = $t->getId();
        for ($i = 1; $i < 20; $i++) {
            if (!isset($_GET["GradeBoundary-grade-new-for-test-$tId-$i"])) {
                break;
            }
            $newGrade = $_GET["GradeBoundary-grade-new-for-test-$tId-$i"];
            $newBoundary = $_GET["GradeBoundary-boundary-new-for-test-$tId-$i"];
            if (!empty($newGrade) && !empty($newBoundary)) {
                $b = new GradeBoundary([
                    GradeBoundary::NAME => $newGrade,
                    GradeBoundary::BOUNDARY => $newBoundary,
                    GradeBoundary::TEST_ID => $tId,
                ]);
                $b->commit();
            }
        }
    }
}

$subjects = Subject::retrieveAll(Subject::NAME);
$tests = Test::retrieveAll(Test::NAME);

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
<form method="get">
<table class="table table-hover table-bordered table-sm">
<thead>
	<tr>
		<th>Subject</th>
		<th>Test name</th>
		<th>Topic<!-- TODO --></th>
		<th>Total sect A</th>
		<th>Total sect B</th>
		<th>Custom grade boundaries?</th>
	</tr>
</thead>

<?php
foreach ($tests as $t) {
    $tId = $t->getId();
    // Subject
    echo "<tr><td><select name=\"" . Test::SUBJECT_ID . "-$tId\">";
    foreach ($subjects as $s) {
        if ($s->getId() == $t->get(Test::SUBJECT_ID)) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        echo "<option value=\"" . $s->getId() . "\" $selected>" . $s->getName() . "</option>";
    }
    echo "</select></td>";    
    // Test name
    echo View::makeTextBoxCell(Test::NAME . "-$tId", $t->get(Test::NAME));
    // TODO Topic
    echo "<td>&nbsp;</td>";
    // Total score
    echo View::makeTextBoxCell(Test::TOTAL_A . "-$tId", $t->get(Test::TOTAL_A));
    echo View::makeTextBoxCell(Test::TOTAL_B . "-$tId", $t->get(Test::TOTAL_B));
    // Custom grade boundaries?
    if ($t->get(Test::CUSTOM_GRADE_BOUNDARIES)) {
        $checked = "checked";
    } else {
        $checked = "";
    }
    echo "<td><div class=\"custom-control custom-checkbox\">";
    echo "<input type=\"checkbox\" class=\"custom-control-input\" id=\"custom-$tId\" name=\"" . Test::CUSTOM_GRADE_BOUNDARIES . "-$tId\" $checked>";
    echo "<label class=\"custom-control-label\" for=\"custom-$tId\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>";
    echo "</div></td>";
    echo "</tr>";
}
?>
<tr>
	<td>
		<select name="newtest-<?= Test::SUBJECT_ID?>">
	    <?php 
	    foreach ($subjects as $s) {
            echo "<option value=\"" . $s->getId() . "\">" . $s->getName() . "</option>";
        }
        ?>
		</select>
	</td>
	<?php
	echo View::makeTextBoxCell("newtest-" . Test::NAME, "");

	// Topics not implemented yet
	echo "<td>&nbsp;</td>";
	
	echo View::makeTextBoxCell("newtest-" . Test::TOTAL_A, "");
	
	echo View::makeTextBoxCell("newtest-" . Test::TOTAL_B, "");
	?>

    <td>
    	<div class="custom-control custom-checkbox">
    		<input type="checkbox" class="custom-control-input" name="newtest-<?= Test::CUSTOM_GRADE_BOUNDARIES ?>" id="custom-newtest">
    		<label class="custom-control-label" for="custom-newtest">&nbsp;&nbsp;</label>
    	</div>
    </td>
</tr>

</table>
<input type="submit" class="form-control" value="Save">

<div class="row">You can set default grade boundaries for tests in each subject (these are PERCENTAGES, based on Section B):</div>

<?php
// Subject grade boundaries
foreach ($subjects as $s) {
    echo "<table class=\"table table-hover table-bordered table-sm\">";
    $gradeArray = [];
    $boundaryArray = [];
    $columns = 0;
    // We'll give whatever's already there + 20 columns; that should be enough!
    foreach (GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, -$s->getId(), GradeBoundary::BOUNDARY) as $b) {
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

// Test grade boundaries
$explain = "<div class=\"row\">You can now set custom grade boundaries for tests that have custom grades enabled (these are on RAW SCORE for Section B):</div>";
foreach ($tests as $t) {
    if (!$t->get(Test::CUSTOM_GRADE_BOUNDARIES)) {
        continue;
    }
    echo $explain;
    $explain = '';
    echo "<table class=\"table table-hover table-bordered table-sm\">";
    $gradeArray = [];
    $boundaryArray = [];
    $columns = 0;
    // We'll give whatever's already there + 20 columns; that should be enough!
    $existingBoundaries = GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, $t->getId(), GradeBoundary::BOUNDARY);
    if (empty($existingBoundaries)) {
        foreach ($t->getGradeBoundaries(true) as $b) {
            (new GradeBoundary([
                GradeBoundary::TEST_ID => $t->getId(),
                GradeBoundary::NAME => $b->get(GradeBoundary::NAME),
                GradeBoundary::BOUNDARY => $b->get(GradeBoundary::BOUNDARY)
            ]))->commit();
        }
        $existingBoundaries = GradeBoundary::retrieveByDetail(GradeBoundary::TEST_ID, $t->getId(), GradeBoundary::BOUNDARY);
    }
    
    foreach ($existingBoundaries as $b) {
        array_push($gradeArray, View::makeTextBoxCell("GradeBoundary-grade-" . $b->getId(), $b->get(GradeBoundary::NAME)));
        array_push($boundaryArray, View::makeTextBoxCell("GradeBoundary-boundary-" . $b->getId(), $b->get(GradeBoundary::BOUNDARY)));
        $columns++;
    }
    for ($i = 1; $i < 20; $i++) {
        array_push($gradeArray, View::makeTextBoxCell("GradeBoundary-grade-new-for-test-". $t->getId() . "-$i", ""));
        array_push($boundaryArray, View::makeTextBoxCell("GradeBoundary-boundary-new-for-test-". $t->getId() . "-$i", ""));
        $columns++;
    }
    echo "<thead><tr><th>" . $t->getName() . "</th>";
    while ($columns-- != 0) {
        echo "<th class=\"th-sm\">&nbsp;</th>";
    }
    echo "</tr></thead>";
    echo "<tr><th>Grade</th>";
    echo implode("", $gradeArray);
    echo "</tr>";
    echo "<tr><th>Minimum section B mark</th>";
    echo implode("", $boundaryArray);
    echo "</tr>";
    echo "</table>";
}


?>
</form>
</div>
</body>
</html>