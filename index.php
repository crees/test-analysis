<?php
namespace TestAnalysis;

require "bin/classes.php";

$allSubjects = Subject::retrieveAll(Subject::NAME);

if (isset($_GET['subject']) && !empty($_GET['subject'])) {
    $subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
    $teachingGroups = $subject->getTeachingGroups();
    $tests = Test::retrieveByDetail(Test::SUBJECT_ID, $_GET['subject']);
    
    if (isset($_GET['teaching_group']) && !empty($_GET['teaching_group'])) {
        $teaching_group = $_GET['teaching_group'];
        $students = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $teaching_group)[0]->getStudents();
    } else {
        $students = $subject->getStudents();
    }
    if (isset($_POST['form_serial']) && $_POST['form_serial'] == $_SESSION['form_serial'] - 1) {
        foreach ($tests as $t) {
            $testId = $t->getId();
            foreach ($students as $s) {
                $studentId = $s->getId();
                $currentResult = $t->getResult($s);
                $newscore = [];
                foreach ([TestResult::SCORE_A, TestResult::SCORE_B] as $res) {
                    if ($_POST["$res-$testId-$studentId"] !== "") {
                        $newscore[$res] = $_POST["$res-$testId-$studentId"];
                    }
                }
                if (isset($newscore[TestResult::SCORE_A]) && isset($newscore[TestResult::SCORE_B])) {
                    // New scores entered
                    if (is_null($currentResult) ||
                        $currentResult->get(TestResult::SCORE_A) != $newscore[TestResult::SCORE_A] ||
                        $currentResult->get(TestResult::SCORE_B) != $newscore[TestResult::SCORE_B]
                        )
                        (new TestResult([
                                        TestResult::ID => null,
                                        TestResult::SCORE_A => $newscore[TestResult::SCORE_A],
                                        TestResult::SCORE_B => $newscore[TestResult::SCORE_B],
                                        TestResult::STUDENT_ID => $studentId,
                                        TestResult::TEST_ID => $testId
                            ])
                        )->commit();
                }
            }
        }
    }
}

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
                		<a class="nav-link" href="?session_destroy=<?= $_SESSION['SESSION_CREATIONTIME']; ?>">Home</a>
                	</li>
            		<li class="nav-item">
                		<a class="nav-link" href="dev">Manage database</a>
                	</li>
            	</ul>
        	</div>
        </nav>

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
        				foreach ($allSubjects as $s) {
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
        		<?php if (isset($_GET['subject'])) { ?>
            		<label for="teaching_group" class="col-2 col-form-label">Teaching group</label>
            		<div class="col-10">
            			<select class="form-control" id="teaching_group" name="teaching_group" onchange="this.form.submit()">
            				<option value="">All groups</option>
            				<?php
            				foreach ($teachingGroups as $g) {
            				    if (isset($_GET['teaching_group']) && $_GET['teaching_group'] === $g->getId()) {
            				        $selected = "selected";
            				    } else {
            				        $selected = "";
            				    }
            				    echo "<option value=\"" . $g->getId() . "\" $selected>" . $g->getName() . "</option>";
            				}
            				?>
            			</select>
          			</div>
          		<?php } /* isset($_GET['subject']) */ ?>
    		</div>
		</form>
		<?php 
		if (isset($tests)) {
		    if (count($tests) < 1) {
		        echo "</tr></table></form><div>No tests defined for selected subject.</div>";
		        return;
		    }
		    echo <<< eof
        <form method="POST">
            <input type="submit" class="form-control" value="Save">
            <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th rowspan="2" scope="col">Name</th>
                        <th rowspan="2" scope="col">Group</th>
                        <th rowspan="2" scope="col">Ind.</th>
eof;
		    foreach ($tests as $t) {
		        if (isset($teaching_group)) {
		          $link = "feedback_sheet.php?teaching_group=$teaching_group&subject={$subject->getId()}&test={$t->getId()}";
		          echo "<th colspan=\"4\" class=\"text-center\"><a href=\"$link\">{$t->getName()}</a></th>\n";
		        } else {
		          echo "<th colspan=\"4\" class=\"text-center\">{$t->getName()}</th>\n";
		        }
		    }
		    echo "</tr>\n<tr>";
		    
		    foreach ($tests as $t) {
		        echo "<th>A</th><th>B</th><th>%</th><th>Grd</th>\n";
		    }
		    echo "</tr>\n</thead>\n";
		    
		    $firstTabIndex = 0;
		    $studentCount = count($students);
		    
		    foreach ($students as $s) {
		        $firstTabIndex++;
		        $tabIndex = $firstTabIndex;
		        echo "<tr>\n";
		        echo "<th><a href=\"student_individual_scores.php?student=" . $s->getId() . "\">" . $s->getName() . "</a></th>\n";
		        echo "<th>" . $s->getTeachingGroup($subject) . "</th>";
		        $baseline = $s->getBaseline($subject);
		        echo "<th>$baseline</th>";
		        foreach ($tests as $t) {
		            $result = $t->getResult($s);
		            echo View::makeTextBoxCell(TestResult::SCORE_A . "-" . $t->getId() . "-" . $s->getId(), is_null($result) ? "" : $result->get(TestResult::SCORE_A), $tabIndex);
		            $tabIndex++;
		            echo View::makeTextBoxCell(TestResult::SCORE_B . "-" . $t->getId() . "-" . $s->getId(), is_null($result) ? "" : $result->get(TestResult::SCORE_B), $tabIndex);
		            if (is_null($result)) {
		                echo "<td>&nbsp;</td><td>&nbsp;</td>";
		            } else {
		                echo "<td>" . round(($result->get(TestResult::SCORE_A)+$result->get(TestResult::SCORE_B)) * 100 / ($t->get(Test::TOTAL_A)+$t->get(Test::TOTAL_B)), 0) . "</td>";
		                $grade = $t->calculateGrade($result);
		                $cellColour = "";
		                if (!empty($baseline)) {
		                    if ($grade == $baseline) {
		                        $cellColour = "class=\"table-warning\"";
		                    } else {
		                        foreach ($t->getGradeBoundaries() as $boundary) {
		                            if ($baseline == $boundary->getName()) {
		                                $cellColour = "class=\"table-danger\"";
		                                break;
		                            }
		                            if ($grade == $boundary->getName()) {
		                                // Greater
		                                $cellColour = "class=\"table-success\"";
		                                break;
		                            }
		                        }
		                    }
		                }
		                echo "<td $cellColour>";
		                
		                echo "$grade";
		                
		                echo "</td>";
		            }
		            $tabIndex += $studentCount;
		        }
		        echo "</tr>\n";
		    }
		    
		    echo <<< eof
            </table>
            </div>
            <input type="hidden" name="form_serial" value="{$_SESSION['form_serial']}">
        </form>
eof;
		}
		?>
	</div>
</body>

</html>