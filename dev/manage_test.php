<?php
namespace TestAnalysis;

include "../bin/classes.php";

$test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
$components = $test->getTestComponents();

if (isset($_GET['test']) && !isset($_POST['form_serial'])) {
    if (isset($_GET['bigTextBox'])) {
        echo "<form method=\"post\"><textarea name=\"bigTextBox\"></textarea>";
        echo "<input type=\"hidden\" name=\"form_serial\" value=\"{$_SESSION['form_serial']}\">";
        echo "<input type=\"hidden\" name=\"test\" value=\"{$_GET['test']}\">";
        echo "<input type=\"submit\">";
        echo "</form>";
        die();
    }
    if (isset($_GET['delete'])) {
        TestComponent::delete($_GET['delete']);
        if (count($components) == 1 && $_GET['delete'] === $components[0]->getId()) {
            // Remove from all subjects
            foreach (TestSubjectMembership::retrieveByDetail(TestSubjectMembership::TEST_ID, $test->getId()) as $m) {
                TestSubjectMembership::delete($m->getId());
            }
            
            // Delete any custom grade boundaries
            foreach (GradeBoundary::retrieveByDetails([GradeBoundary::TEST_ID, GradeBoundary::BOUNDARY_TYPE], [$test->getId(), GradeBoundary::TYPE_TEST]) as $g) {
                GradeBoundary::delete($g->getId());
            }
            
            // Delete the entire test!
            Test::delete($test->getId());
            header("Location: manage_tests.php");
            die();
        }
        header("Location: manage_test.php?test={$_GET['test']}");
        die();
    }
} else if (isset($_POST['form_serial']) && (session_status() != PHP_SESSION_ACTIVE || $_POST['form_serial'] == $_SESSION['form_serial'] - 1)){
    $test = Test::retrieveByDetail(Test::ID, $_POST['test'])[0];
    $components = $test->getTestComponents();
    $targets = $test->get(Test::TARGETS);
    if (isset($_POST['bigTextBox'])) {
        $targets = [];
        foreach (explode('<br>', nl2br($_POST['bigTextBox'], false)) as $line) {
            if (!preg_match('/\w/', $line)) {
                continue;
            }
            array_push($targets, trim($line));
        }
        $test->set(Test::TARGETS, $targets);
    } else {
        foreach ($components as $component) {
            foreach ([TestComponent::NAME, TestComponent::TOTAL, TestComponent::INCLUDED_IN_PERCENT, TestComponent::INCLUDED_IN_GRADE, TestComponent::INCLUDED_FOR_TARGETS, TestComponent::INCLUDED_IN_REGRESSION] as $field) {
                $data = "component-$field-{$component->getId()}";
                if (isset($_POST[$data])) {
                    $component->set($field, $_POST[$data]);
                } else {
                    $component->set($field, false);
                }
            }
            $component->commit();
        }
        
        if (!empty($_POST['component-total-new']) && $_POST['component-total-new'] > 0) {
            (new TestComponent([
                TestComponent::NAME => $_POST['component-name-new'],
                TestComponent::TOTAL => $_POST['component-total-new'],
                TestComponent::TEST_ID => $test->getId(),
            ]))->commit();
            $components = $test->getTestComponents(true);
        }
        
        if (isset($_POST['custom_boundaries'])) {
            $test->set(Test::CUSTOM_GRADE_BOUNDARIES, 1);
            // Let's now examine the grade boundaries.  First handle any that have changed
            foreach (GradeBoundary::retrieveByDetail(GradeBoundary::BOUNDARY_TYPE, GradeBoundary::TYPE_TEST) as $b) {
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
            for ($i = 1; $i < 20; $i++) {
                if (!isset($_POST["GradeBoundary-grade-new-for-test-$i"])) {
                    break;
                }
                $newGrade = $_POST["GradeBoundary-grade-new-for-test-$i"];
                $newBoundary = $_POST["GradeBoundary-boundary-new-for-test-$i"];
                if ($newGrade !== "" && $newBoundary !== "") {
                    $b = new GradeBoundary([
                        GradeBoundary::NAME => $newGrade,
                        GradeBoundary::BOUNDARY => $newBoundary,
                        GradeBoundary::TEST_ID => $test->getId(),
			GradeBoundary::BOUNDARY_TYPE => GradeBoundary::TYPE_TEST,
                    ]);
                    $b->commit();
                }
            }
        } else {
            $test->set(Test::CUSTOM_GRADE_BOUNDARIES, 0);
        }
        
        // Targets
        for ($i = 0; $i < Config::max_targets; $i++) {
            if (!isset($targets[$i])) {
                $targets[$i] = '';
            }
            if (isset($_POST["test-{$test->getId()}-target-$i"]) && 
                    ($_POST["test-{$test->getId()}-target-$i"] != $targets[$i]))
            {
                for ($j = 0; $j < Config::max_targets; $j++) {
                    $targets[$j] = $_POST["test-{$test->getId()}-target-$j"];
                }
                $test->set(Test::TARGETS, $targets);
            }
        }
    }
    $test->commit();
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
            	<li class="nav-item">
            		<a class="nav-link" href="manage_tests.php">Manage tests</a>
            	</li>
            	<?php 
            	if (empty($test->get(Test::TARGETS)[0])) {
            	?>
            	<li class="nav-item">
            		<a class="nav-link" href="?test=<?= $test->getId() ?>&bigTextBox=yes">Use large text box</a>
            	</li>
            	<?php
            	} ?>
        	</ul>
    	</div>
    </nav>
<form method="post">
<table class="table table-hover table-sm">
<tr><td colspan="13"><input class="form-control btn btn-success" type="submit" value="Save"></td></tr>
<thead>
	<tr>
		<th colspan="6"><?= $test->getName(); ?></th>

	</tr>
</thead>
<?php
echo "<tr><th>Test component name</th><th>Total</th><th>Include in percent</th><th>Include in grade</th><th>Include in target calculation</th><th>Include in regression calculation</th><th>Delete</th></tr>";
foreach ($components as $component) {
    echo "<tr>";
    echo View::makeTextBoxCell("component-name-{$component->getId()}", $component->getName());
    echo View::makeTextBoxCell("component-total-{$component->getId()}", $component->getTotal(), 0, 'number');
    foreach ([TestComponent::INCLUDED_IN_PERCENT, TestComponent::INCLUDED_IN_GRADE, TestComponent::INCLUDED_FOR_TARGETS, TestComponent::INCLUDED_IN_REGRESSION] as $box) {
        $checked = $component->get($box) == 1 ? " checked" : "";
        echo "<td><input type=\"checkbox\" name=\"component-$box-{$component->getId()}\"" . $checked . "></td>";
    }
    if (count(TestComponentResult::retrieveByDetail(TestComponentResult::TESTCOMPONENT_ID, $component->getId())) > 0) {
        echo "<td>(results exist, delete not allowed)</td>";
    } elseif (count(ScannedTest::retrieveByDetail(ScannedTest::TEST_ID, $test->getId())) > 0) {
        echo "<td>(scanned tests exist, delete not allowed)</td>";
    } else {
        echo "<td><a href=\"?test={$test->getId()}&delete={$component->getId()}\" class=\"btn btn-sm btn-danger\">";
        if (count($components) == 1) {
            echo "&#x1f4a3; Delete this entire test (and targets and grade boundaries if they exist)";
        } else {
            echo "Delete";
        }
        echo "</a></td>";
    }
    echo "</tr>";
}

echo "<tr>";
echo View::makeTextBoxCell("component-name-new", "", 0, 'text', 'placeholder="New component name"');
echo View::makeTextBoxCell("component-total-new", "", 0, 'number', 'placeholder="New component max score"');
echo "</tr>";

echo "<tr><th colspan=6>Targets</th></tr>";

for ($i = 0; $i < Config::max_targets; $i++) {
    echo "<tr>";
    echo "<th>Target " . ($i+1) . "</th>";
    if (isset($test->get(Test::TARGETS)[$i])) {
        $value = $test->get(Test::TARGETS)[$i];
    } else {
        $value = "";
    }
    echo View::makeTextBoxCell("test-{$test->getId()}-target-$i", $value, 0, "text", "", "colspan=5");
    echo "</tr>";
}

echo "</table>";
?>

<div class="row">
    <label for="custom_boundaries" class="col-6 col-form-label">Custom grade boundaries</label>
    
    <div class="col-6">
		<input type="checkbox" class="form-control" id="custom_boundaries" name="custom_boundaries" <?= $test->get(Test::CUSTOM_GRADE_BOUNDARIES) ? "checked" : "" ?>>
	</div>
</div>

<?php
if ($test->get(Test::CUSTOM_GRADE_BOUNDARIES)) {
    echo "<table class=\"table table-hover table-bordered table-sm\">";
    $gradeArray = [];
    $boundaryArray = [];
    $columns = 0;
    // We'll give whatever's already there + 20 columns; that should be enough!
    $existingBoundaries = GradeBoundary::retrieveByDetails([GradeBoundary::TEST_ID, GradeBoundary::BOUNDARY_TYPE], [$test->getId(), GradeBoundary::TYPE_TEST], GradeBoundary::BOUNDARY);
    if (empty($existingBoundaries)) {
        // Arbitrarily base off the first subject match for the test
        $memberships = TestSubjectMembership::retrieveByDetail(TestSubjectMembership::TEST_ID, $test->getId());
        if (isset($memberships[0])) {
            $membership = $memberships[0];
            $subject = Subject::retrieveByDetail(Subject::ID, $membership->get(TestSubjectMembership::SUBJECT_ID))[0];
            foreach (GradeBoundary::retrieveByDetails([GradeBoundary::TEST_ID, GradeBoundary::BOUNDARY_TYPE], [$subject->getId(), GradeBoundary::TYPE_SUBJECT]) as $b) {
                (new GradeBoundary([
                    GradeBoundary::TEST_ID => $test->getId(),
                    GradeBoundary::NAME => $b->get(GradeBoundary::NAME),
                    GradeBoundary::BOUNDARY => $b->get(GradeBoundary::BOUNDARY),
		    GradeBoundary::BOUNDARY_TYPE => GradeBoundary::TYPE_TEST
                ]))->commit();
            }
            $existingBoundaries = GradeBoundary::retrieveByDetails([GradeBoundary::TEST_ID, GradeBoundary::BOUNDARY_TYPE], [$test->getId(), GradeBoundary::TYPE_TEST], GradeBoundary::BOUNDARY);
        } else {
            $existingBoundaries = [];
        }
    }
    
    foreach ($existingBoundaries as $b) {
        array_push($gradeArray, View::makeTextBoxCell("GradeBoundary-grade-" . $b->getId(), $b->get(GradeBoundary::NAME)));
        array_push($boundaryArray, View::makeTextBoxCell("GradeBoundary-boundary-" . $b->getId(), $b->get(GradeBoundary::BOUNDARY)));
        $columns++;
    }
    for ($i = 1; $i < 20; $i++) {
        array_push($gradeArray, View::makeTextBoxCell("GradeBoundary-grade-new-for-test-$i", ""));
        array_push($boundaryArray, View::makeTextBoxCell("GradeBoundary-boundary-new-for-test-$i", ""));
        $columns++;
    }
    echo "<thead><tr><th>" . $test->getName() . "</th>";
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
</table>
<input type="hidden" name="form_serial" value="<?= $_SESSION['form_serial'] ?>">
<input type="hidden" name="test" value="<?= $_GET['test'] ?>">
</form>
</div>
</body>
</html>
