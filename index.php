<?php
namespace TestAnalysis;

require "bin/classes.php";

$staff = Staff::me($auth_user);

$departments = $staff->getDepartments(true);
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

<body onload="colouriseAll()">
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
            <input type="button" id="editbutton" class="form-control btn btn-success" value="Edit values" onclick="inputify()">
            <input type="button" id="exportbutton" class="form-control btn btn-warning" value="Export to Excel" onclick="excel_export()">
            <div class="table-responsive table-95 table-stickyrow">
            <table class="table table-bordered table-sm table-hover" id="data-table">
                <thead>
                    <tr>
                        <th scope="col">&nbsp;</th>
                        <th scope="col">&nbsp;</th>
                        <th scope="col">&nbsp;</th>
                        <th scope="col">&nbsp;</th>
eof;
		    foreach ($tests as $t) {
                $colspan = 0;
                $percentComponentExists = false;
                $gradeComponentExists = false;
                foreach ($t->getTestComponents() as $c) {
                    if ($c->get(TestComponent::INCLUDED_IN_PERCENT)) {
                        $percentComponentExists = true;
                    }
                    if ($c->get(TestComponent::INCLUDED_IN_GRADE)) {
                        $gradeComponentExists = true;
                    }
                    $colspan++;
                }
		        $colspan += ($percentComponentExists ? 1 : 0) + ($gradeComponentExists ? 1 : 0);
		        if (isset($teaching_group)) {
		          $link = "feedback_sheet.php?teaching_group={$teaching_group->getId()}&subject={$subject->getId()}&test={$t->getId()}";
		          echo "<th colspan=\"$colspan\" class=\"text-center\"><a href=\"$link\">{$t->getName()}</a></th>\n";
		        } else {
		          echo "<th colspan=\"$colspan\" class=\"text-center\">{$t->getName()}</th>\n";
		        }
		    }
		    echo "</tr>\n<tr class=\"excel-filtered\"><th scope=\"col\">Name</th><th>Group</th><th>Ind.</th><th>CWAG</th>";
		    
		    foreach ($tests as $t) {
		        $percentComponentExists = false;
		        $gradeComponentExists = false;
		        foreach ($t->getTestComponents() as $c) {
		            if ($c->get(TestComponent::INCLUDED_IN_PERCENT)) {
		                $percentComponentExists = true;
		            }
		            if ($c->get(TestComponent::INCLUDED_IN_GRADE)) {
		                $gradeComponentExists = true;
		            }
		            echo "<td>{$c->getName()}</td>";
		        }
		        if ($percentComponentExists) {
		            echo "<td>%</td>";
		        }
		        if ($gradeComponentExists) {
		            echo "<td>Grd</td>\n";
		        }
		    }
		    echo "</tr>\n</thead>\n";
		    
		    $staffNames = [];
		    foreach (Staff::retrieveAll() as $stf) {
		        $staffNames[$stf->getId()] = $stf->getName();
		    }
		    $staffNames[0] = "Unknown";
		    
		    foreach ($students as $s) {
		        echo "<tr>\n";
		        echo "<th scope=\"row\" studentId=\"{$s->getId()}\"><a href=\"student_individual_scores.php?student=" . $s->getId() . "\">" . $s->getLastFirstName() . "</a></th>\n";
		        echo "<td>";
		        echo ($s->getLabel('group') ?? $teaching_group)->getName();
		        echo "</td>";
		        $baseline = $s->getShortIndicative($subject);
		        echo "<td id=\"baseline-{$s->getId()}\">$baseline</td>";
		        
		        $cwag = $s->getAverageGrade($subject) ?? '&nbsp;';
		        echo "<td id=\"cwag-0-{$s->getId()}\">$cwag</td>";
		        
		        foreach ($tests as $t) {
		            $results = $t->getTestComponentResults($s);
		            foreach ($t->getTestComponents() as $c) {
		                $result = $results[$c->getId()][0] ?? null;
		                $popupResults = [];
		                foreach ($results[$c->getId()] as $r) {
                            $date = date("y-m-d", $r->get(TestComponentResult::RECORDED_TS));
                            array_push($popupResults, "{$r->get(TestComponentResult::SCORE)}, $date, {$staffNames[$r->get(TestComponentResult::STAFF_ID)]}");
		                }
	                    $title = empty($popupResults) ? '' : "title=\"" . implode('&#xA;', $popupResults) . "\"";
		                $highlight = (count($popupResults) > 1) ? 'corner-mark' : '';
		                echo "<td class=\"score-input $highlight\" $title id=\"" . TestComponentResult::SCORE . "-{$c->getId()}-{$s->getId()}\">" . (is_null($result) ? "" : $result->get(TestComponentResult::SCORE)) . "</td>";
		            }
		            $percent = $t->calculatePercent($s);
		            if (!is_null($percent)) {
		                echo "<td id=\"percent-{$t->getId()}-{$s->getId()}\">$percent</td>";
		            }
		            $grade = $t->calculateGrade($s, $subject);
		            if (!is_null($grade)) {
		                echo "<td id=\"grade-{$t->getId()}-{$s->getId()}\">$grade</td>";
		            }
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
const score = '<?= TestComponentResult::SCORE; ?>';
const tests = [<?php foreach ($tests as $t) { echo "{$t->getId()}, ";} ?>];
const testWithComponents = {<?php 
foreach ($tests as $t) {
    echo "{$t->getId()}: [";
    foreach ($t->getTestComponents() as $c) {
        echo "{$c->getId()}, ";
    }
    echo "], ";
}
?>};

const testComponents = [<?php foreach ($tests as $t) { foreach ($t->getTestComponents() as $c) { echo "{$c->getId()}, ";}} ?>];
const students = [<?php foreach ($students as $s) { echo "{$s->getId()}, ";} ?>]
const gradeboundaries = {
<?php
echo "\t0: {";
foreach ($subject->getGradeBoundaries() as $boundary) {
    // Here we prepend grade/ to ensure that they are all treated as strings.
    // This makes sure they appear in the right order!
    echo "'grade/{$boundary->getName()}': {$boundary->get(GradeBoundary::BOUNDARY)}, ";
}
echo "},\n";
foreach ($tests as $t) {
    echo "\t{$t->getId()}: {";
    foreach ($t->getGradeBoundaries($subject) as $boundary) {
        echo "'grade/{$boundary->getName()}': {$boundary->get(GradeBoundary::BOUNDARY)}, ";
    }
    echo "},\n";
}
?>
};

function inputify() {
	$('input#editbutton')[0].hidden=true;
	$('input#exportbutton')[0].hidden=true;
	tds = $('td.score-input');
	tabindex = 1;
	for (t of tests) {
		for (s of students) {
			for (tc of testWithComponents[t]) {
    			id = [score, tc, s].join('-');
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
    			elements[0].innerHTML = '<input class="form-control border-0 px-1" tabindex="' + tabindex + '" value="' + val + '" type="number" onwheel="this.blur()" id="' + id + '" onchange="save(\'' + tc + '\', \'' + s + '\')">';
    			elements[0].classList.add('nopadding');
    			tabindex++;
    		}
		}
	}
}

function excel_export() {
	var table = $('table#data-table')[0];
	// Add Arbor IDs
	table.rows[1].insertCell(0).appendChild(document.createTextNode("Arbor ID"));
	for (r of table.lastChild.rows) {
		r.insertCell(0).appendChild(document.createTextNode(r.cells[1].attributes["studentId"].value));
	}
	colouriseAll(literalColours = true);
	// Grab the title rows and add filter element.
	for (th of $('tr.excel-filtered')[0].children) {
		th.setAttribute('filter', 'ALL');
	}
	for (s of students) {
		for (t of tests) {
			cells = $('td#' + ['grade', t, s].join('-'));
			if (cells.length > 0) {
				cells[0].innerHTML = '="' + cells[0].innerHTML + '"';
			}
		}
		cells = $('td#' + ['cwag', 0, s].join('-'));
		if (cells.length > 0) {
			cells[0].innerHTML = '="' + cells[0].innerHTML + '"';
		}
		cells = $('td#baseline-' + s);
		if (cells.length > 0) {
			cells[0].innerHTML = '="' + cells[0].innerHTML + '"';
		}
	}
	var link = document.createElement('a');
    link.download = "export.xls";
    link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(table.outerHTML);
    link.click();
    location.reload();
}

function colouriseAll(literalColours = false) {
	for (s of students) {
		for (t of tests) {
			colourise([['percent', t, s].join('-')], literalColours);
			colourise([['grade', t, s].join('-')], literalColours);
		}
		calcCwag(s, literalColours);
	}
}

function save(testComponentId, studentId) {

	element = $('input#' + score + '-' + testComponentId + '-' + studentId);
	cwag = $('#' + ['cwag', 0, studentId].join('-'));
	
	result = parseInt(element[0].value);

	if (isNaN(result)) {
		return;
	}

	element[0].style.color = '#FFfa00';

	if (false && cwag.length > 0) {
		cwag[0].innerHTML = '';
	}

	var xhr = new XMLHttpRequest();
    xhr.open("POST", 'async/newscore.php', true);
    
    //Send the proper header information along with the request
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          saved(element[0], studentId, this.responseText);
        }
        console.log(this.responseText);
    };
    xhr.send("studentId=" + studentId + "&testComponentId=" + testComponentId + "&result=" + result + "&subjectId=<?= $subject->getId() ?>");
}

function saved(element, studentId, responseText) {
	if (responseText.includes('Total out of range')) {
		element.style.color = '#ffa500';
		return;
	}
	if (responseText.includes('Other failure')) {
		window.alert('Save failed for the red scores.  Please email <?= Config::site_supportemail ?> and say what you were trying to do.');
		return;
	}
	element.style.color = '#00ff00';
	changes = responseText.split(',');
	for (i = 0; i < changes.length; i++) {
		if (changes[i].length > 0) {
    		change = changes[i].split(':');
    		$('#' + change[0])[0].innerHTML = change[1];
    		colourise(change);
		}
	}
	calcCwag(studentId);
}

function colourise(arr, literalColours = false) {
	elements = $('#' + arr[0])
	if (elements.length == 0)
		return;
	element = elements[0];
	components = arr[0].split('-');
	switch (components[0]) {
	case 'percent':
		percent = parseInt(element.innerText);
		if (isNaN(percent)) {
			return;
		}
		switch(Math.trunc(percent / 25)) {
		case 0:
			element.style.color = '#dc3545';
			break;
		case 1: case 2:
			element.style.color = '#ffc107';
			break;
		default:
			element.style.color = '#28a745';
			break;
		}
		break;
	case 'grade': case 'cwag':
		testId = components[1];
		studentId = components[2];
		grade = element.innerText.trim();
		if (grade.length == 0) {
			return;
		}
		baseline = $('td#baseline-' + studentId)[0].innerText.trim();
		if (baseline.length == 0) {
			return;
		}
		// We find the boundary for each grade, and go from the grade below
		// So this is rather nasty, and it relies on the grades being reverse sorted
		// as they are from TestAnalysis\Test and TestAnalysis\Subject
		gradeb = null;
		// If there isn't a grade scored for the test, let's just score zero
		if (gradeboundaries[testId]['grade/'+grade] == null) {
			gradeb = 0;
		} else {
			// We will keep going until we match the grade, then award green until the 'next' grade
    		previousGrade = null;
    		for (g in gradeboundaries[testId]) {
        		// If they get the very top grade...
				if (previousGrade == null) {
					previousGrade = g;
				}
				// Aha, we've matched the grade, so let's (for colour purposes) award them one above
    			if (g.replace('grade/', '') == grade) {
    				gradeb = gradeboundaries[testId][previousGrade];
    				break;
    			}
    			previousGrade = g;
    		}
		}
		baselineb = gradeboundaries[testId]['grade/'+baseline] ?? null;
		if (baselineb == null) {
			return;
		}

		if (gradeb == baselineb) {
			element.style.backgroundColor = literalColours ? '#ffeeba' : 'var(--grade-on)';
		} else if (gradeb > baselineb) {
			element.style.backgroundColor = literalColours ? '#c3e6cb' : 'var(--grade-above)';
		} else {
			element.style.backgroundColor = literalColours ? '#f5c6cb' : 'var(--grade-below)';
		}
	}
}

function calcCwag(studentId, literalColours) {
	cwagElement = $('#' + ['cwag', 0, studentId].join('-'))[0];

	total_gradeboundaries = 0;
	number_gradeboundaries = 0;
	
	for (t of tests) {
		gradeElements = $('#' + ['grade', t, studentId].join('-'));
		if (gradeElements.length == 0)
			continue;
		gradeElement = gradeElements[0];
		grade = gradeElement.innerText;
		if (grade !== "") {
			total_gradeboundaries += gradeboundaries[0]['grade/'+grade] ?? 0;
			number_gradeboundaries++;
		}
	}
	if (number_gradeboundaries < 4) {
		cwagElement.innerText = "-";
		cwagElement.title = "Four or more test results required for a currently working at grade";
		return;
	}
	avg_gradeboundaries = total_gradeboundaries / number_gradeboundaries;
	// Look up grade from grade boundary
	lower = -1;
	for (grade in gradeboundaries[0]) {
		if (gradeboundaries[0][grade] < avg_gradeboundaries && gradeboundaries[0][grade] > lower) {
			lower = gradeboundaries[0][grade];
		}
	}
	cwag = null;
	for (grade in gradeboundaries[0]) {
		if (gradeboundaries[0][grade] === lower) {
			cwag = grade.replace('grade/', '');
			break;
		}
	}
	cwagElement.innerText = cwag ?? 0;
	colourise([['cwag', 0, studentId].join('-')], literalColours);
}

</script>
</body>

</html>