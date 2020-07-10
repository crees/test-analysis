<?php 
namespace TestAnalysis;

require "bin/classes.php";

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
    		<span class="navbar-text">
        		<a class="nav-link" href="dev">Manage database</a>
        	</span>
        </nav>
        
        <div><img src="img/dshs.jpg" style="width: 30%;" /></div>

		<h3 class="mb-4"><?= Config::site ?> (skillset overview)</h3>

<form method="GET">
<div class="form-group row">
<label for="subject" class="col-2 col-form-label">Subject</label>
<div class="col-10">
<select class="form-control" id="subject" name="subject" onchange="this.form.submit()">
<?php
if (!isset($_GET['subject'])) {
    echo "<option value=\"\" selected>Please select subject</option>";
}
foreach (Subject::retrieveAll(Subject::NAME) as $s) {
    if (isset($_GET['subject']) && $_GET['subject'] === $s->getId()) {
        $selected = "selected";
    } else {
        $selected = "";
    }
    echo "<option value=\"" . $s->getId() . "\" $selected>" . $s->getName() . "</option>";
}
?>
        			</select>
        		</div>
        		<?php if (!isset($_GET['subject'])) {
        		    die("</div></form></div></body></html>");
        		} else {
        		    $subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
        		    $teachingGroups = $subject->getTeachingGroups();
        		    echo <<< EOF
            		<label for="teaching_group" class="col-2 col-form-label">Teaching group</label>
            		<div class="col-10">
            			<select class="form-control" id="teaching_group" name="teaching_group" onchange="this.form.submit()">
            				<option value="">All groups</option>
EOF;
            				foreach ($teachingGroups as $g) {
            				    if (isset($_GET['teaching_group']) && $_GET['teaching_group'] === $g->getId()) {
            				        $selected = "selected";
            				    } else {
            				        $selected = "";
            				    }
            				    echo "<option value=\"" . $g->getId() . "\" $selected>" . $g->getName() . "</option>";
            				}
            				echo <<< EOF
            			</select>
          			</div>
EOF;
        		} /* isset($_GET['subject']) */ ?>
    		</div>
		</form>
		<?php 
		if (!empty($tests = $subject->getTests(Test::NAME))) {
		    echo <<< EOF
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th rowspan="2" scope="col">Name</th>
                        <th rowspan="2" scope="col">Group</th>
                        <th rowspan="2" scope="col">Ind.</th>
EOF;
		    foreach ($tests as $test) {
		        echo "<th colspan=4>{$test->getName()}</th>";
		    }
		    echo <<< EOF
                    </tr>
                    
                    <tr>
EOF;
		    for ($j = 0; $j < count($tests); $j++) {
		        for ($i = 1; $i <= 4; $i++) {
		            echo "<th>$i</th>";
		        }
		    }
		    echo <<< EOF
                    </tr>
                </thead>
EOF;
		    if (isset($_GET['teaching_group']) && !empty($_GET['teaching_group'])) {
		        $teaching_group = $_GET['teaching_group'];
		        $students = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $teaching_group)[0]->getStudents();
		    } else {
		        $students = $subject->getStudents();
		    }
		    
		    foreach ($students as $student) {
		        echo "<tr>";
		        echo "<th>{$student->getName()}</th>";
		        echo "<th>{$student->getTeachingGroup($subject)}</th>";
		        echo "<th>{$student->getBaseline($subject)}</th>";
		        foreach ($tests as $test) {
		            if (is_null($result = $test->getResult($student))) {
		                for ($i = 0; $i < 4; $i++) {
		                    echo "<td>&nbsp;</td>";
		                }
		                continue;
		            }
		            $b_percentage = $result->get(TestResult::SCORE_B) * 100 / $test->get(Test::TOTAL_B);
		            $colour_changed = false;
		            for ($i = 0; $i < 4; $i++) {
		                if ($b_percentage - $i * 25 > 0) {
		                    echo "<td class=\"bg-success\">G</td>";
		                } else {
		                    if ($colour_changed) {
		                        echo "<td class=\"bg-danger\">R</td>";
		                    }
		                    else {
		                        $colour_changed = true;
		                        echo "<td class=\"bg-warning\">A</td>";
		                    }
		                }
		            }
		        }
                    		            
		        echo "</tr>";
		    }
		    
		    echo <<< EOF
            </table>
        </div>
EOF;
		} else {
		    echo "<div>No Tests set for {$subject->getName()}</div>";
		}
		?>
	</div>
</body>

</html>