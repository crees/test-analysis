<?php
namespace TestAnalysis;

include "../bin/classes.php";

$staff = Staff::me($auth_user);

$departments = $staff->getAdminDepartments(true);

if (isset($_GET['removeTest'])) {
    Subject::retrieveByDetail(Subject::ID, $_GET['removeFromSubject'])[0]->removeTest(Test::retrieveByDetail(Test::ID, $_GET['removeTest'])[0]);
    $url = explode("?", "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")[0];
    header("Location: $url");
} else {
    foreach ($_POST as $k => $value) {
        if (!empty($value)) {
            if (str_contains($k, "subject-add-test-")) {
                $test = Test::retrieveByDetail(Test::ID, str_replace("subject-add-test-", "", $k))[0];
                $subject = Subject::retrieveByDetail(Subject::ID, $value)[0];
                $subject->addTest($test);
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
<thead><tr><th>Test</th><th>Subjects (click to remove)</th><th>Add subject</th></tr></thead>
<?php
foreach ($departments as $dept) {
    echo "<tr><th colspan=\"3\">{$dept->getName()}</th></tr>";
    foreach (Test::retrieveByDetail(Test::DEPARTMENT_ID, $dept->getId(), Test::NAME) as $t) {
        $allSubjects = Subject::retrieveByDetail(Subject::DEPARTMENT_ID, $dept->getId(), Subject::NAME);
        echo "<tr>";
        echo "<td>{$t->getName()}";
        $names = [];
        foreach ($t->getSubjects() as $s) {
            array_push($names, "<a href=\"?removeTest=" . $t->getId() . "&removeFromSubject=" . $s->getId() . "\">" . $s->getName() . "</a>");
            unset($allSubjects[array_search($s, $allSubjects)]);
        }
        echo "<td>" . implode(", ", $names) . "</td>";
        
        echo "<td><select name=\"subject-add-test-" . $t->getId() . "\" onchange=\"this.form.submit()\">";
        echo "<option value=\"\" selected>Add subject to " . $t->getName() . "</option>";
        foreach ($allSubjects as $s) {
            echo "<option value=\"" . $s->getId() . "\">" . $s->getName() . "</option>";
        }
        echo "</select></td>";
        echo "</tr>";
    }
}
?>
</table>
</form>
</div>
</body>
</html>