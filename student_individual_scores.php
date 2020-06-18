<?php
namespace TestAnalysis;

require "bin/classes.php";

if (!isset($_GET['student'])) {
    header('location: index.php');
    die("No student provided, back home we go.");
}

$student = Student::retrieveByDetail(Student::ID, $_GET['student']);

if (!isset($student[0])) {
    header('location: index.php');
    die("Fake student??");
}

if (isset($_GET['resultToDelete'])) {
    TestResult::delete($_GET['resultToDelete']);
}

$student = $student[0];

?>
<!doctype html>
<html>

<head>
<?php require "bin/head.php"; ?>
</head>

<body>
	<div class="container">
        <nav class="navbar navbar-expand">
            <!-- Brand -->
            <a class="navbar-brand">Actions</a>
            
            <!-- Toggler/collapsibe Button -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
            	<span class="navbar-toggler-icon">collapse</span>
            </button>
            
            <!-- Navbar links -->
            <div class="collapse navbar-collapse" id="collapsibleNavbar">
            	<ul class="navbar-nav">
            		<li class="nav-item">
                		<a class="nav-link" href="index.php">Home</a>
                	</li>
            	</ul>
        	</div>
        </nav>

		<h3 class="mb-4"><?= Config::site ?></h3>
		
		<h4 class="mb-4">Student detail viewer for <?= $student->getName() ?></h4>
		
		<div>Here is a raw list of student scores-- you can delete one by clicking on it.  Take care!</div>
		
		<table class="table table-hover">
			<thead>
				<tr>
					<th>Test name</th>
					
					<th>Result</th>
				
					<th>Date entered</th>
				</tr>
			</thead>
			<?php
			foreach (TestResult::retrieveByDetail(TestResult::STUDENT_ID, $student->getId(), TestResult::TEST_ID . ', ' . TestResult::RECORDED_TS) as $r) {
                $link = "<a href=\"?student=" . $student->getId() . "&resultToDelete=" . $r->getid() . "\" class=\"stretched-link\">";
			    echo "<tr>";
                echo "<td>$link" . $r->getTest()[0]->getName() . "</a></td>";
                echo "<td>$link" . $r->getScore() . "</a></td>";
                echo "<td>$link" . date("Y-m-d H:i:s", $r->get(TestResult::RECORDED_TS)) . "</a></td>";
                echo "</tr>";
			}
			?>
		</table>
		
	</div>
</body>

</html>