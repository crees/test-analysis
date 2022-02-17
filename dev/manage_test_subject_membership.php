<?php
namespace TestAnalysis;

include "../bin/classes.php";

$staff = Staff::me($auth_user);

$departments = $staff->getAdminDepartments(true);

if (isset($_GET['removeTest'])) {
    Subject::retrieveByDetail(Subject::ID, $_GET['removeFromSubject'])[0]->removeTest(Test::retrieveByDetail(Test::ID, $_GET['removeTest'])[0]);
//    $url = explode("?", "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")[0];
//    header("Location: $url");
    die();
} else {
    if (!empty($_GET['addTest']) && !empty($_GET['addToSubject'])) {
        $test = Test::retrieveByDetail(Test::ID, $_GET['addTest'])[0];
        $subject = Subject::retrieveByDetail(Subject::ID, $_GET['addToSubject'])[0];
        $subject->addTest($test);
        die("{$test->getId()} {$subject->getId()} {$subject->getName()}");
    }
}
?>
<!doctype html>
<html><head><?php require "../bin/head.php" ?>
<script>
function addTest(testId) {
	select = $('select#subject-add-test-' + testId)[0];
	subjectSelOption = $('select#subject-add-test-' + testId + ' option:selected')[0];
	subjectId = subjectSelOption.value;
	if (subjectId != "") {
    	select.remove(subjectSelOption.index);
    	var xhr = new XMLHttpRequest();
        xhr.addEventListener("load", testAdded);
        xhr.open("GET", 'manage_test_subject_membership.php?addTest=' + testId + '&addToSubject=' + subjectId);
    	xhr.send();
	}
}

function testAdded() {
	m = this.response.match(/(\d+) (\d+) (.+)/);
	test = m[1];
	sId = m[2];
	subject = m[3];
	cell = $('td#test-subjects-list-' + test)[0];
	cell.innerHTML += ' <a href="javascript:;" id="removeTest-' + test + '-removeFromSubject-' + sId + '" onclick="removeTest(' + test + ', ' + sId + ')">' + subject + '</a>';
}

function removeTest(testId, subjectId) {
	a = $('a#removeTest-' + testId + '-removeFromSubject-' + subjectId)[0];
	subjectName = a.innerText;
	a.remove();
	sel = $('select#subject-add-test-' + testId)[0];
	sel.add(new Option(subjectName, subjectId));
	var xhr = new XMLHttpRequest();
    xhr.open("GET", 'manage_test_subject_membership.php?removeTest=' + testId + '&removeFromSubject=' + subjectId);
	xhr.send();
}
</script>
</head>
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
<table class="table table-sm table-hover">
<thead><tr><th>Test</th><th>Subjects (click to remove)</th><th>Add subject</th></tr></thead>
<?php
foreach ($departments as $dept) {
    echo "<tr><th colspan=\"3\">{$dept->getName()}</th></tr>";
    foreach (Test::retrieveByDetail(Test::DEPARTMENT_ID, $dept->getId(), Test::NAME) as $t) {
        $allSubjects = Subject::retrieveByDetail(Subject::DEPARTMENT_ID, $dept->getId(), Subject::NAME);
        echo "<tr>";
        echo "<td id=\"testName-{$t->getId()}\">{$t->getName()}";
        $names = [];
        foreach ($t->getSubjects() as $s) {
            array_push($names, "<a href=\"javascript:;\" id=\"removeTest-" . $t->getId() . "-removeFromSubject-" . $s->getId() . "\" onclick=\"removeTest({$t->getId()}, {$s->getId()})\">" . $s->getName() . "</a>");
            unset($allSubjects[array_search($s, $allSubjects)]);
        }
        echo "<td id=\"test-subjects-list-{$t->getId()}\">" . implode(" ", $names) . "</td>";
        
        echo "<td><select id=\"subject-add-test-" . $t->getId() . "\" onchange=\"addTest({$t->getId()})\">";
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
</div>
</body>
</html>