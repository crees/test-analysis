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

		<h3 class="mb-4"><?= Config::site ?></h3>

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
		if (!empty($topics = TestTopic::retrieveByDetail(TestTopic::SUBJECT_ID, $subject->getId()))) {
		    echo <<< EOF
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Group</th>
                        <th>Ind.</th>
EOF;
		    foreach ($topics as $topic) {
		        echo "<th>{$topic->getName()}</th>";
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
		        foreach ($topics as $topic) {
		            $percents_b = [];
		            $tests = $topic->getTests();
		            if (count($tests) == 0) {
		                echo "<td>&nbsp;</td>";
		                continue;
		            }
		            foreach ($tests as $test) {
		                if (is_null($result = $test->getResult($student))) {
		                    continue;
		                }
		                array_push($percents_b, $result->get(TestResult::SCORE_B) * 100 / $test->get(Test::TOTAL_B));
		            }
		            if (count($percents_b) == 0) {
		                $average_b = -100;
		            } else {
		                $average_b = array_sum($percents_b) / count($percents_b);
		            }
		            
		            // OK, so there are four levels possible- emerging, developing, secure, mastered
		            switch ((int)($average_b / 25)) {
		                case 0:
		                    $colour = "danger";
		                    $val = "E";
		                    break;
		                case 1:
		                    $colour = "warning";
		                    $val = "D";
		                    break;
		                case 2:
		                    $colour = "success";
		                    $val = "S";
		                    break;
		                case 3: case 4:
		                    $colour = "primary";
		                    $val = "M";
		                    break;
		                default:
		                    $colour = "secondary";
		                    $val = "-";
		                    break;
		            }
		            echo "<td class=\"table-$colour\">$val</td>";
		            //echo "<td>average $average_b</td>";
		        }
                    		            
		        echo "</tr>";
		    }
		    
		    echo <<< EOF
            </table>
        </div>
EOF;
		} else {
		    echo "<div>No Topics set for {$subject->getName()}</div>";
		}
		?>
	</div>
</body>

</html>