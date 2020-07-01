<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_GET['test'])) {
    $test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
} else foreach (Test::retrieveAll(Test::ID) as $test) {
    $targets = $test->get(Test::TARGETS);
    for ($i = 0; $i < $test->getSubject()->get(Subject::NUM_TARGETS); $i++) {
        if (!isset($targets[$i])) {
            $targets[$i] = '';
        }
        if (isset($_GET["test-{$test->getId()}-target-$i"]) && 
                ($_GET["test-{$test->getId()}-target-$i"] != $targets[$i]))
        {
            for ($j = 0; $j < $test->getSubject()->get(Subject::NUM_TARGETS); $j++) {
                $targets[$j] = $_GET["test-{$test->getId()}-target-$j"];
            }
            $test->set(Test::TARGETS, $targets);
            $test->commit();
            break;
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
        	</ul>
    	</div>
    </nav>
<form method="get">
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
</form>
</div>
</body>
</html>