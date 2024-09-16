<?php
namespace TestAnalysis;

// So we have a special case here.  If the user has selected subject *and* teaching group
// *and* has a matching key, no need to auth.  Also, flag that we don't want headers.

if (!empty($_GET['key']) && !empty($_GET['subject']) && isset($_GET['teaching_group']) && !empty($_GET['user'])) {
    $auth_key_prefix = "subject:{$_GET['subject']};teaching_group:{$_GET['teaching_group']};user:{$_GET['user']}";
}

require "bin/classes.php";

if ($key_authed ?? false == true) {
    $table_only = true;
    $staff = Staff::retrieveByDetail(Staff::ID, $_GET['user'])[0];
} else {
    $staff = Staff::me($auth_user);
}

$departments = $staff->getDepartments(true);
$allSubjects = [];
foreach ($departments as $d) {
    foreach (Subject::retrieveByDetail(Subject::DEPARTMENT_ID, $d->getId(), Subject::NAME) as $s) {
        // @var Subject $s
        if (empty($s->getTeachingGroups()))
            continue;
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
<?php if (!$table_only) require "bin/head.php"; ?>
</head>

<body onload="colouriseAll()">
	<div class="container">
		<?php if (!$table_only) require "bin/navbar.php"; ?>
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
        				    echo "$subjectName (<a href=\"" . Config::site_url . "\">Change this</a>)";
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
		    $livedatalink = "?subject={$_GET['subject']}&teaching_group={$_GET['teaching_group']}&user={$staff->getId()}&key=" . auth_generate_key("subject:{$_GET['subject']};teaching_group:{$_GET['teaching_group']};user:{$staff->getId()}");
		    echo <<< eof
            <input type="button" id="editbutton" class="form-control btn btn-success" value="Edit values" onclick="inputify()" />
            <input type="button" id="exportbutton" class="form-control btn btn-warning" value="Export to Excel" onclick="excel_export()" />
            <input type="button" id="kpiButton" class="form-control btn btn-primary" value="Show headlines" onclick="showHeadlines()" />
            <div class="table-responsive table-95 table-stickyrow">
            <table class="table table-bordered table-sm table-hover" id="data-table">
                <thead>
                    <tr id="top_row">
                        <td scope="col"><a href="$livedatalink" title="Copy this link and use Excel's Data>Get Data>From Web and paste into there.  No need for authentication with this, but don't then share the spreadsheet outside the organisation!">Live data link</a></td>
eof;
		    View::makeStudentTableHeading(true);
		    if (isset($table_only) && $table_only) {
		        echo '<th scope="col">&nbsp;</th>';
		    }
		    foreach ($tests as $t) {
                $colspan = 0;
                $percentComponentExists = false;
                $gradeComponentExists = false;
                $regressionComponentExists = false;
                foreach ($t->getTestComponents() as $c) {
                    if ($c->get(TestComponent::INCLUDED_IN_PERCENT)) {
                        $percentComponentExists = true;
                    }
                    if ($c->get(TestComponent::INCLUDED_IN_GRADE)) {
                        $gradeComponentExists = true;
                    }
                    if ($c->get(TestComponent::INCLUDED_IN_REGRESSION)) {
                        $regressionComponentExists = true;
                    }
                    $colspan++;
                }
                foreach ([$percentComponentExists, $gradeComponentExists, $regressionComponentExists] as $extra) {
                    if ($extra) {
                        $colspan++;
                    }
                }
		        if (isset($teaching_group) && !empty($t->get(Test::TARGETS))) {
		          $link = "feedback_sheet.php?teaching_group={$teaching_group->getId()}&subject={$subject->getId()}&test={$t->getId()}";
		          echo "<th colspan=\"$colspan\" class=\"text-center\"><a href=\"$link\">{$t->getName()}</a></th>\n";
		        } else {
		          echo "<th colspan=\"$colspan\" class=\"text-center\">{$t->getName()}</th>\n";
		        }
		    }
		    echo "</tr>\n<tr class=\"excel-filtered\" id=\"subtitle_row\">";
		    if (isset($table_only) && $table_only) {
		        echo '<th scope="col">Arbor ID</th>';
		    }
		    
		    View::makeStudentTableHeading(false);
		    
		    foreach ($tests as $t) {
		        $percentComponentParts = [];
		        $gradeComponentParts = [];
		        $regressionComponentParts = [];
		        foreach ($t->getTestComponents() as $c) {
		            if ($c->get(TestComponent::INCLUDED_IN_PERCENT)) {
		                $percentComponentParts[] = $c->getName();
		            }
		            if ($c->get(TestComponent::INCLUDED_IN_GRADE)) {
		                $gradeComponentParts[] = $c->getName();
		            }
		            if ($c->get(TestComponent::INCLUDED_IN_REGRESSION)) {
		                $regressionComponentParts[] = $c->getName();
		            }
		            echo "<td>{$c->getName()}</td>";
		        }
		        if (!empty($percentComponentParts)) {
		            $title = "Calculated from Section " . implode(', ', $percentComponentParts) . '.';
		            $title .= '&#13;&#13;This is the percentage out of the total.';		            
		            echo "<td title=\"$title\">%</td>";
		        }
		        if (!empty($gradeComponentParts)) {
		            $title = "Calculated from Section " . implode(', ', $gradeComponentParts) . '.';
		            $title .= '&#13;&#13;This is the grade calculated from the grade boundaries set in &quot;Manage Database&quot;.';
		            echo "<td title=\"$title\" grade_for_test=\"{$t->getId()}\">Grd</td>";
		        }
		        if (!empty($regressionComponentParts)) {
		            $title = "Calculated from Section " . implode(', ', $regressionComponentParts) . '.';
		            $title .= '&#13;&#13;This is the regression calculated by taking the percentage for every student, and then ';
		            $title .= 'finding a regression line against indicative grades, and comparing the expected percentage with the ';
		            $title .= 'achieved percentage.  Each &gt; or &lt represents 5 percentage points above/below respectively.';
		            echo "<td title=\"$title\">Reg</td>";
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
		        if (isset($table_only) && $table_only) {
		            echo "<th scope=\"row\">{$s->getId()}</th>";
		        }
		        View::makeStudentTableRow($s, $teaching_group ?? null, $subject);
		        
		        $cwag = $s->getAverageGrade($subject) ?? '&nbsp;';
		        $ppValue = is_null($s->getLabel('PupilPremium')) ? 0 : 1;
		        $senValue = is_null($s->getLabel('SENNeed')) ? 0 : 1;
		        $baseline = $s->getShortIndicative($subject);
		        echo "<td id=\"cwag-0-{$s->getId()}\" baseline=\"$baseline\" pp=\"$ppValue\" sen=\"$senValue\">$cwag</td>";
		        
		        foreach ($tests as $t) {
		            /** @var $t Test */
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
		                echo "<td id=\"grade-{$t->getId()}-{$s->getId()}\" pp=\"$ppValue\" sen=\"$senValue\" baseline=\"$baseline\">$grade</td>";
		            }
		            $matches = [];
		            $tg = $teaching_group ?? $s->getLabel('group');
		            if (preg_match('/^[0-9]+/', $tg->getName(), $matches) != 0) {
		                $regression = $t->calculateRegression($matches[0], $s, $subject);
		                if (!empty($regression)) {
		                    switch ($regression) {
	                        case '_':
	                            $title = 'Insufficient results for calculation';
	                            break;
	                        case '~':
	                            $title = 'Insufficient student numbers in groups for calculation';
	                            break;
	                        case '?':
	                            $title = 'Baseline either missing or not a number';
	                            break;
	                        default:
	                            $title = '';
		                    }

		                    echo "<td id=\"regression-{$t->getId()}-{$s->getId()}\" title=\"$title\">$regression</td>";
		                }
		            }
		        }
		        echo "</tr>\n";
		    }
		    
		    echo <<< eof
            </table>
            </div>
eof;
		}
		if (isset($_GET['key'])) {
		    die("<!--Do not allow users to use keys to mess around as other users!-->");
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
	$('td[id^=regression]').empty();
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
	for (var r of $('tr.headline-row')) {
		r.insertCell(0).appendChild(document.createTextNode(" "));
	}
	$('tr#top_row')[0].insertCell(0).appendChild(document.createTextNode(" "));
	$('tr#subtitle_row')[0].insertCell(0).appendChild(document.createTextNode("Arbor ID"));
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
		if (gradeboundaries[0]['grade/'+grade] == null) {
			gradeb = 0;
			console.log("Fail finding grade!");
		} else {
			// We will keep going until we match the grade, then award green until the 'next' grade
    		previousGrade = null;
    		for (g in gradeboundaries[0]) {
    			if (g.replace('grade/', '') == grade) {
    				gradeb = gradeboundaries[0][g];
    				break;
    			}
    		}
		}
		baselineb = gradeboundaries[0]['grade/'+baseline] ?? null;
		if (baselineb == null) {
			return;
		}

		// So now we have baselineb, we need to work out how far above/below we are
		grade_diff = 0;

		if (gradeb < baselineb) {
			var previous_gradeb = -1;
			for (g in gradeboundaries[0]) {
				if (gradeboundaries[0][g] == previous_gradeb)
					continue;
				if (gradeboundaries[0][g] > gradeb && gradeboundaries[0][g] <= baselineb) {
					grade_diff--;
				}
				previous_gradeb = gradeboundaries[0][g];
			}
		} else if (gradeb > baselineb) {
			var previous_gradeb = -1;
			for (g in gradeboundaries[0]) {
				if (gradeboundaries[0][g] == previous_gradeb)
					continue;
				if (gradeboundaries[0][g] < gradeb && gradeboundaries[0][g] >= baselineb)
					grade_diff++;
				previous_gradeb = gradeboundaries[0][g];
			}
		}

		element.setAttribute('ta_grade_diff', grade_diff);

		if (grade_diff == -1) {
			element.style.backgroundColor = literalColours ? '#ffeeba' : 'var(--grade-on)';
		} else if (grade_diff > -1) {
			element.style.backgroundColor = literalColours ? '#c3e6cb' : 'var(--grade-above)';
		} else {
			element.style.backgroundColor = literalColours ? '#f5c6cb' : 'var(--grade-below)';
		}
	}
}

function showHeadlines() {

	const TYPE_DIFF = 0;
	const TYPE_IGR_PERCENT = 1;
	const TYPE_GRADE_PERCENT = 2;
	
	const row_types = [
		{
			"label": "Av. diff",
			"type": TYPE_DIFF,
			"pp": 0,
			"sen": 0
		}, {
			"label": "&gt;=IGR %",
			"type": TYPE_IGR_PERCENT,
			"pp": 0,
			"sen": 0
		}, {
			"label": "&gt;=IGR % PP",
			"type": TYPE_IGR_PERCENT,
			"pp": 1,
			"sen": 0
		}, {
			"label": "&gt;=IGR % non-PP",
			"type": TYPE_IGR_PERCENT,
			"pp": -1,
			"sen": 0
		}, {
			"label": "&gt;=IGR % SEN",
			"type": TYPE_IGR_PERCENT,
			"pp": 0,
			"sen": 1
		}, {
			"label": "&gt;=IGR % HPA",
			"type": TYPE_IGR_PERCENT,
			"pp": 0,
			"sen": 0,
			"baseline_min": 7
		}, {
			"label": "&gt;=IGR % MPA",
			"type": TYPE_IGR_PERCENT,
			"pp": 0,
			"sen": 0,
			"baseline_min": 5,
			"baseline_max": 6
		}, {
			"label": "&gt;=IGR % LPA",
			"type": TYPE_IGR_PERCENT,
			"pp": 0,
			"sen": 0,
			"baseline_max": 4
		}, {
			"label": ">=4 %",
			"type": TYPE_GRADE_PERCENT,
			"pp": 0,
			"sen": 0,
			"grade_min": 4
		}, {
			"label": ">=5 %",
			"type": TYPE_GRADE_PERCENT,
			"pp": 0,
			"sen": 0,
			"grade_min": 5
		}, {
			"label": ">=7 %",
			"type": TYPE_GRADE_PERCENT,
			"pp": 0,
			"sen": 0,
			"grade_min": 7
		}
	];

	var rows = [];
	
	$('#kpiButton')[0].hidden=true;

	for (var r in row_types) {
		rows[r] = headlineRow(row_types[r].label);
	}
	for (var c of $('tr#subtitle_row')[0].childNodes) {
		if (c.innerHTML == 'Name')
			continue;
		if (c.attributes['grade_for_test'] === undefined) {
			for (var r in rows) {
				rows[r].append(document.createElement('td'));
			}
			continue;
		}
		// We've found a Test Grade, let's get all of the Grade for Tests
		var test;
		var grades;
		if (c.attributes['grade_for_test'].nodeValue == 0) {
			grades = $('td[id^=cwag-0-]');
		} else {
    		test = c.attributes['grade_for_test'].nodeValue;
    		grades = $('td[id^=grade-' + test + '-]');
		}
		var count = [];
		var total = [];
		for (var r in rows) {
			count[r] = 0;
			total[r] = 0;
		}
		for (var g of grades) {
			var gdiff = g.attributes['ta_grade_diff'];
			if (gdiff === undefined) {
				continue;
			}
			gdiff = parseInt(gdiff.nodeValue);
			var studentBaselineb = gradeboundaries[0]['grade/' + g.attributes['baseline'].nodeValue];
			for (var r in rows) {
				// Check for IGR range min and max
				if (row_types[r].baseline_min !== undefined) {
					var minBaselineb = gradeboundaries[0]['grade/' + row_types[r].baseline_min];
					if (minBaselineb > studentBaselineb)
						continue;
				}
				if (row_types[r].baseline_max !== undefined) {
					var maxBaselineb = gradeboundaries[0]['grade/' + row_types[r].baseline_max];
					if (maxBaselineb < studentBaselineb)
						continue;
				}
				if (g.attributes['sen'].nodeValue == 0 && row_types[r].sen == 1)
					continue;
				if (g.attributes['sen'].nodeValue == 1 && row_types[r].sen == -1)
					continue;
				if (g.attributes['pp'].nodeValue == 0 && row_types[r].pp == 1)
					continue;
				if (g.attributes['pp'].nodeValue == 1 && row_types[r].pp == -1)
					continue;
				count[r]++;
				switch (row_types[r].type) {
				case TYPE_DIFF:
					total[r] += gdiff;
					break;
				case TYPE_IGR_PERCENT:
    				if (gdiff >= 0)
        				total[r]++;
    				break;
				case TYPE_GRADE_PERCENT:
    				if (row_types[r].grade_min !== undefined) {
    					if (gradeboundaries[0]['grade/' + g.innerHTML] >= gradeboundaries[0]['grade/' + row_types[r].grade_min])
    						total[r]++;
    				}
    				break;
				}
			}
		}
		for (var r in rows) {
			var cell = document.createElement('td');
			switch (row_types[r].type) {
			case TYPE_DIFF:
    			if (count[r] > 0)
    				cell.innerHTML = (total[r] / count[r]).toFixed(2);
				break;
			case TYPE_IGR_PERCENT:
			case TYPE_GRADE_PERCENT:
    			if (count[r] > 0)
    				cell.innerHTML = Math.round(total[r] * 100 / count[r]);
				break;
			}
			rows[r].append(cell);
		}
	}

	var top_row = $('tr#top_row')[0];

	for (var r of rows) {
		top_row.parentElement.insertBefore(r, top_row);
	}
}

function headlineRow(title) {

	var row = document.createElement('tr');
	row.classList.add('headline-row');
	var h = document.createElement('th');
	h.setAttribute('scope', 'row');
	h.innerHTML = title;
	row.append(h);
	return row;
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
	if (number_gradeboundaries < 2) {
		cwagElement.innerText = "-";
		cwagElement.title = "Two or more test results required for a currently working at grade";
		return;
	}
	avg_gradeboundaries = total_gradeboundaries / number_gradeboundaries;
	// Look up grade from grade boundary
	lower = -1;
	for (grade in gradeboundaries[0]) {
		if (gradeboundaries[0][grade] <= avg_gradeboundaries && gradeboundaries[0][grade] > lower) {
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
