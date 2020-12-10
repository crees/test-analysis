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
        $teaching_group = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['teaching_group'])[0];
        $students = $teaching_group->getStudents();
    } else {
        $students = [];
        foreach ($teachingGroups as $group) {
            foreach ($group->getStudents() as $gStudent) {
                $gStudent->setLabel('group', $group);
                array_push($students, $gStudent);
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

<body onload="inputify()">
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
            <div class="table-responsive table-95 table-stickyrow">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th scope="col">&nbsp;</th>
                        <th scope="col">&nbsp;</th>
                        <th scope="col">&nbsp;</th>
eof;
		    foreach ($tests as $t) {
		        if (isset($teaching_group)) {
		          $link = "feedback_sheet.php?teaching_group={$teaching_group->getId()}&subject={$subject->getId()}&test={$t->getId()}";
		          echo "<th colspan=\"4\" class=\"text-center\"><a href=\"$link\">{$t->getName()}</a></th>\n";
		        } else {
		          echo "<th colspan=\"4\" class=\"text-center\">{$t->getName()}</th>\n";
		        }
		    }
		    echo "<th scope=\"col\">MLG</th>";
		    echo "</tr>\n<tr><th scope=\"col\">Name</th><th>Group</th><th>Ind.</th>";
		    
		    foreach ($tests as $t) {
		        if ($t->get(Test::TOTAL_A) > 0) {
		            echo "<td>A</td><td>B</td><td>A%</td><td>Grd</td>\n";
		        } else {
		            echo "<td>&nbsp;</td><td>Total</td><td>%</td><td>Grd</td>\n";
		        }
		    }
		    echo "<td>&nbsp;</td></tr>\n</thead>\n";
		    
		    foreach ($students as $s) {
		        echo "<tr>\n";
		        echo "<th scope=\"row\"><a href=\"student_individual_scores.php?student=" . $s->getId() . "\">" . $s->getName() . "</a></th>\n";
		        echo "<td>";
		        echo ($s->getLabel('group') ?? $teaching_group)->getName();
		        echo "</td>";
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
		                echo "<td class=\"score-input\" id=\"" . TestResult::SCORE_A . "-{$t->getId()}-{$s->getId()}\">" . (is_null($result) ? "" : $result->get(TestResult::SCORE_A)) . "</td>";
		            } else {
		                echo ("<td>&nbsp;</td>");
		            }
		            echo "<td class=\"score-input\" id=\"" . TestResult::SCORE_B . "-{$t->getId()}-{$s->getId()}\">" . (is_null($result) ? "" : $result->get(TestResult::SCORE_B)) . "</td>";
		            if (is_null($result)) {
		                echo "<td id=\"percent-{$t->getId()}-{$s->getId()}\">&nbsp;</td><td id=\"grade-{$t->getId()}-{$s->getId()}\">&nbsp;</td>";
		            } else {
		                if ($t->get(Test::TOTAL_A) > 0) {
		                    $percent_A = $result->get(TestResult::SCORE_A) * 100 / $t->get(Test::TOTAL_A);
		                } else {
		                    $percent_A = $result->get(TestResult::SCORE_B) * 100 / $t->get(Test::TOTAL_B);
		                }
		                echo "<td id=\"percent-{$t->getId()}-{$s->getId()}\">" . round($percent_A, 0) . "</td>";
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
eof;
		}
		?>
	</div>
	
<script>
const scoreA = '<?= TestResult::SCORE_A; ?>';
const scoreB = '<?= TestResult::SCORE_B; ?>';
const tests = [<?php foreach ($tests as $t) { echo "{$t->getId()}, ";} ?>];
const students = [<?php foreach ($students as $s) { echo "{$s->getId()}, ";} ?>]

function inputify() {
	tds = $('td.score-input');
	tabindex = 1;
	for (t of tests) {
		for (s of students) {
			for (score of [scoreA, scoreB]) {
    			id = [score, t, s].join('-');
				elements = $('td#' + id)
				if (elements.length == 0) {
					continue;
				}
    			val = elements[0].innerHTML;
    			if (val.match('[<>]')) {
					// HTML present, something already there-- not sure how this can happen
					console.log("Already HTML in " + id + ", weird!");
					continue;
    			}
                //echo val, tabindex, "number", onchange=\"save('{$t->getId()}', '{$s->getId()}', '" . TestResult::SCORE_A . "')\"");
    			elements[0].innerHTML = '<input class="form-control border-0 px-1" tabindex="' + tabindex + '" value="' + val + '" type="number" id="' + id + '" onchange="save(\'' + t + '\', \'' + s + '\')">';
				elements[0].classList.add('nopadding');
				tabindex++;
    		}
    		colourise([['percent', t, s].join('-')]);
		}
	}
}

function save(testId, studentId) {
	// Get both
	elementA = $('input#' + scoreA + '-' + testId + '-' + studentId);
	elementB = $('input#' + scoreB + '-' + testId + '-' + studentId);
	mlg = $('#' + 'mlg-' + studentId);
	
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
		elementA[0].style.color = '#FFfa00';
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
		percent = parseInt(element.innerText);
		if (isNaN(percent)) {
			return;
		}
		switch(Math.trunc(percent / 33)) {
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