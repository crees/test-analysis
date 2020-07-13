<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_GET['test']) && !isset($_POST['form_serial'])) {
    $test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
    if (isset($_GET['bigTextBox'])) {
        echo "<form method=\"post\"><textarea name=\"bigTextBox\"></textarea>";
        echo "<input type=\"hidden\" name=\"form_serial\" value=\"{$_SESSION['form_serial']}\">";
        echo "<input type=\"hidden\" name=\"test\" value=\"{$_GET['test']}\">";
        echo "<input type=\"submit\">";
        echo "</form>";
        die();
    }
} else {
    $test = Test::retrieveByDetail(Test::ID, $_POST['test'])[0];
    $targets = $test->get(Test::TARGETS);
    if (isset($_POST['bigTextBox'])) {
        $test->set(Test::TARGETS, explode('<br>', nl2br($_POST['bigTextBox'], false)));
        $test->commit();
    } else for ($i = 0; $i < $test->getSubject()->get(Subject::NUM_TARGETS); $i++) {
        if (!isset($targets[$i])) {
            $targets[$i] = '';
        }
        if (isset($_POST["test-{$test->getId()}-target-$i"]) && 
                ($_POST["test-{$test->getId()}-target-$i"] != $targets[$i]))
        {
            for ($j = 0; $j < $test->getSubject()->get(Subject::NUM_TARGETS); $j++) {
                $targets[$j] = $_POST["test-{$test->getId()}-target-$j"];
            }
            $test->set(Test::TARGETS, $targets);
            $test->commit();
        }
    }
    header('Location: manage_tests.php');
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
<thead>
	<tr>
		<th colspan=2><?= $test->getName(); ?></th>

	</tr>
</thead>
<?php

for ($i = 0; $i < $test->getSubject()->get(Subject::NUM_TARGETS); $i++) {
    echo "<tr>";
    echo "<th>Target " . ($i+1) . "</th>";
    if (isset($test->get(Test::TARGETS)[$i])) {
        $value = $test->get(Test::TARGETS)[$i];
    } else {
        $value = "";
    }
    echo View::makeTextBoxCell("test-{$test->getId()}-target-$i", $value);
    echo "</tr>";
}

?>
<tr><td colspan="13"><input class="form-control" type="submit" value="Save"></td></tr>
</table>
<input type="hidden" name="form_serial" value="<?= $_SESSION['form_serial'] ?>">
<input type="hidden" name="test" value="<?= $_GET['test'] ?>">
</form>
</div>
</body>
</html>