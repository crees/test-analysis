<?php
namespace TestAnalysis;

require "bin/classes.php";
require "dev/upgrade_database.php";

$departments = Department::retrieveAll(Department::NAME);
$allSubjects = [];
foreach ($departments as $d) {
    foreach (Subject::retrieveByDetail(Subject::DEPARTMENT_ID, $d->getId(), Subject::NAME) as $s) {
        $s->setName("{$d->getName()}: {$s->getName()}");
        array_push($allSubjects, $s);
    }
}

if (isset($_GET['subject']) && !empty($_GET['subject'])) {
    $subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
    $teachingGroups = $subject->getTeachingGroups();
    $tests = $subject->getTests();
    
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
		<?php require "bin/navbar.php"; ?>
		<form method="GET">
    		<div class="form-group row">
    			<label for="subject" class="col-2 col-form-label">Subject</label>
        				<?php
        				if (!isset($_GET['subject'])) {
        				    echo '<div class="col-10">';
        				    echo '<select class="form-control" id="subject" name="subject" onchange="this.form.submit()">';
        				    echo "<option value=\"\" selected>Please select subject</option>";
            				foreach ($allSubjects as $s) {
            				    if (sizeof($s->getTests()) == 0) {
            				        continue;
            				    }
            				    echo "<option value=\"" . $s->getId() . "\">" . $s->getName() . "</option>";
            				}
            				echo '</select>';
        				} else {
        				    echo '<div class="col-10 col-form-label">';
        				    echo '<input type="hidden" id="subject" name="subject" value="' . $_GET['subject'] . '">';
        				    $subjectName = Subject::retrieveByDetail(Subject::ID, $_GET['subject']);
        				    if (sizeof($subjectName) !== 1) {
        				        die("Oops, somehow you have put in an invalid Subject");
        				    }
        				    $subjectName = $subjectName[0]->getName();
        				    echo "$subjectName (<a href=\"" . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) . "\">Change this</a>)";
        				}
        				?>
        		</div>
        		<?php if (isset($_GET['subject'])) {
        		    echo <<< EOF
            		<label for="teaching_group" class="col-2 col-form-label">Teaching group</label>
            		<div class="col-10">
            			<select class="form-control" id="teaching_group" name="teaching_group" onchange="this.form.submit()">
EOF;
                		    if (!isset($_GET['teaching_group'])) {
                		        echo "<option value=\"\" selected>Please select a group</option>";
                		        unset ($tests);
                		    }
        		            echo "<option value=\"\">All groups</option>";
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
		if (isset($tests)) {
		    if (count($tests) < 1) {
		        echo "<div>No tests defined for selected subject.</div>";
		        return;
		    }
		    echo <<< eof
        <form method="POST">
            <input type="submit" class="form-control btn btn-warning" value="No need to save any more!  Click here and *let CMR know if new results don't go green.*">
            <div class="table-responsive table-95 table-stickyrow">
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
		    echo "<th rowspan=\"2\" scope=\"col\">MLG</th>";
		    echo "</tr>\n<tr>";
		    
		    foreach ($tests as $t) {
		        if ($t->get(Test::TOTAL_A) > 0) {
		            echo "<td>A</td><td>B</td><td>A%</td><td>Grd</td>\n";
		        } else {
		            echo "<td>&nbsp;</td><td>Total</td><td>%</td><td>Grd</td>\n";
		        }
		    }
		    echo "</tr>\n</thead>\n";
		    
		    $firstTabIndex = 0;
		    $studentCount = count($students);
		    
		    foreach ($students as $s) {
		        $firstTabIndex++;
		        $tabIndex = $firstTabIndex;
		        echo "<tr>\n";
		        echo "<th scope=\"row\"><a href=\"student_individual_scores.php?student=" . $s->getId() . "\">" . $s->getName() . "</a></th>\n";
		        echo "<td>" . $s->getTeachingGroup($subject) . "</td>";
		        $baseline = $s->getShortIndicative($subject);
		        echo "<td>$baseline</td>";
		        $results = TestResult::retrieveByDetail(TestResult::STUDENT_ID, $s->getId(), TestResult::RECORDED_TS . ' DESC');
		        foreach ($tests as $t) {
		            $result = null;
		            foreach ($results as $r) {
		                // Results are sorted by latest timestamp first, so just take first result
		                if ($r->get(TestResult::TEST_ID) == $t->getId()) {
		                    $result = $r;
		                    break;
		                }
		            }
		            if ($t->get(Test::TOTAL_A) > 0) {
    		            echo View::makeTextBoxCell(TestResult::SCORE_A . "-" . $t->getId() . "-" . $s->getId(), is_null($result) ? "" : $result->get(TestResult::SCORE_A), $tabIndex, "number", "min=\"0\" max=\"{$t->get(Test::TOTAL_A)}\" onchange=\"save('{$t->getId()}', '{$s->getId()}', '" . TestResult::SCORE_A . "')\"");
    		            $tabIndex++;
		            } else {
		                echo ("<td>&nbsp;<input type=\"hidden\" name=\"" . TestResult::SCORE_A . "-" . $t->getId() . "-" . $s->getId() . "\" value=\"0\"></td>");
		            }
		            echo View::makeTextBoxCell(TestResult::SCORE_B . "-" . $t->getId() . "-" . $s->getId(), is_null($result) ? "" : $result->get(TestResult::SCORE_B), $tabIndex, "number", "min=\"0\" max=\"{$t->get(Test::TOTAL_B)}\" onchange=\"save('{$t->getId()}', '{$s->getId()}', '" . TestResult::SCORE_B . "')\"");
		            if (is_null($result)) {
		                echo "<td id=\"percent-{$t->getId()}-{$s->getId()}\">&nbsp;</td><td id=\"grade-{$t->getId()}-{$s->getId()}\">&nbsp;</td>";
		            } else {
		                if ($t->get(Test::TOTAL_A) > 0) {
		                    $percent_A = $result->get(TestResult::SCORE_A) * 100 / $t->get(Test::TOTAL_A);
		                } else {
		                    $percent_A = $result->get(TestResult::SCORE_B) * 100 / $t->get(Test::TOTAL_B);
		                }
		                // Don't forget the Javascript below must match!
		                switch (floor($percent_A/33)) {
		                case 0:
		                    $sAcolour = "text-danger";
		                    break;
		                case 1:
		                    $sAcolour = "text-warning";
		                    break;
		                default:
		                    $sAcolour = "text-success";
		                    break;
		                }
		                echo "<td id=\"percent-{$t->getId()}-{$s->getId()}\" class=\"$sAcolour\">" . round($percent_A, 0) . "</td>";
		                $grade = $t->calculateGrade($result, $subject);
		                $cellColour = "";
		                if (!empty($baseline)) {
		                    if ($grade == $baseline) {
		                        $cellColour = "class=\"table-warning\"";
		                    } else {
		                        foreach ($t->getGradeBoundaries($subject) as $boundary) {
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
		                echo "<td $cellColour id=\"grade-{$t->getId()}-{$s->getId()}\">$grade</td>";
		            }
		            $tabIndex += $studentCount;
		        }
		        if (!is_null($grade = $s->getMostLikelyGrade($subject))) {
		            // MLG
		            $cellColour = "";
		            if (!empty($baseline)) {
		                if ($grade == $baseline) {
		                    $cellColour = "class=\"table-warning\"";
		                } else {
		                    foreach ($subject->getGradeBoundaries() as $boundary) {
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
		            echo "<td id=\"mlg-{$s->getId()}\" $cellColour>$grade</td>";
		        } else {
		            echo "<td>&nbsp;</td>";
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
	
<script>
function save(testId, studentId) {
	// Get both
	const scoreA = '<?= TestResult::SCORE_A; ?>';
	const scoreB = '<?= TestResult::SCORE_B; ?>';
	
	elementA = $('#' + scoreA + '-' + testId + '-' + studentId);
	elementB = $('#' + scoreB + '-' + testId + '-' + studentId);
	mlg = $('#' + 'mlg-' + studentId)
	
	if (elementA.length == 0) {
		// There is no element A
		resultA = 0;
	} else {
		resultA = parseInt(elementA[0].value);
	}
	resultB = parseInt(elementB[0].value);

	if (isNaN(resultA) || isNaN(resultB)) {
		return;
	}

	if (resultA != 0) {
		if ((resultA > elementA[0].max) || (resultA < elementA[0].min)) {
			elementA[0].style.color = '#FF0000';
			return;
		}
		elementA[0].style.color = '#FFfa00';
	}
	if (resultB > elementB[0].max || resultB < elementB[0].min) {
		elementB[0].style.color = '#FF0000';
		return;
	}	
	elementB[0].style.color = '#FFfa00';

	if (mlg.length > 0) {
		mlg[0].innerHTML = '';
	}

	var xhr = new XMLHttpRequest();
    xhr.open("POST", 'async/newscore.php', true);
    
    //Send the proper header information along with the request
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          saved(elementA, elementB, this.responseText);
        }
        console.log(this.responseText);
    };
    xhr.send("studentId=" + studentId + "&testId=" + testId + "&a=" + resultA + "&b=" + resultB + "&subjectId=<?= $subject->getId() ?>");
}

function saved(a, b, responseText) {
	if (responseText.includes('Total out of range')) {
		if (a.length != 0) {
			a[0].style.color = '#ffa500';
		}
		b[0].style.color = '#ffa500';
		return;
	}
	if (responseText.includes('Other failure')) {
		window.alert('Save failed for the red scores.  Please email <?= Config::site_supportemail ?> and say what you were trying to do.');
		return;
	}
	if (a.length != 0) {
		a[0].style.color = '#00ff00';
	}
	b[0].style.color = '#00ff00';
	changes = responseText.split(',');
	for (i = 0; i < changes.length; i++) {
		change = changes[i].split(':');
		$('#' + change[0])[0].innerHTML = change[1];
		colourise(change);
	}
}

function colourise(arr) {
	element = $('#' + arr[0])[0];
	if (arr[0].includes('percent')) {
		switch(Math.trunc(arr[1] / 33)) {
		case 0:
			element.className = 'text-danger';
			break;
		case 1:
			element.className = 'text-warning';
			break;
		default:
			element.className = 'text-success';
			break;
		}
	} else if (arr[0].includes('grade')) {
		element.className = arr[2];
	}
}

</script>
</body>

</html>