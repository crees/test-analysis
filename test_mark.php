<?php
namespace TestAnalysis;

require "bin/classes.php";
require "dev/upgrade_database.php";

$allSubjects = Subject::retrieveAll(Subject::NAME);

if (isset($_GET['subject']) && !empty($_GET['subject'])) {
    $subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
    $teachingGroups = $subject->getTeachingGroups();
    $tests = $subject->getTests();
    
    if (isset($_GET['teaching_group']) && !empty($_GET['teaching_group'])) {
        $teaching_group = $_GET['teaching_group'];
        $students = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $teaching_group)[0]->getStudents();
        if (isset($_GET['test']) && !empty($_GET['test'])) {
            $test = Test::retrieveByDetail(Test::ID, $_GET['test']);
            if (count($test) < 1) {
                echo "<div>No tests defined for selected subject.</div>";
                return;
            }
            $test = $test[0];
        }
    } else {
        $students = $subject->getStudents();
    }
    
    if (isset($_POST['form_serial']) && $_POST['form_serial'] == $_SESSION['form_serial'] - 1) {
        /* Deal with submitted data */
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
                		    }
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
    		        if (isset($_GET['teaching_group'])) {
    		            echo <<< EOF
    		            <label for="test" class="col-2 col-form-label">Test</label>
    		            <div class="col-10">
    		            <select class="form-control" id="test" name="test" onchange="this.form.submit()">
EOF;
    		            if (!isset($_GET['test'])) {
    		                echo "<option value=\"\" selected>Please select a test</option>";
    		            }
    		            foreach ($tests as $t) {
    		                if (isset($_GET['test']) && $_GET['test'] === $t->getId()) {
    		                    $selected = "selected";
    		                } else {
    		                    $selected = "";
    		                }
    		                echo "<option value=\"" . $t->getId() . "\" $selected>" . $t->getName() . "</option>";
    		            }
    		            echo <<< EOF
            			</select>
          			</div>
EOF;
    		        }
        		} /* isset($_GET['subject']) */ ?>
    		</div>
		</form>

		<?php
		if (isset($test)) {
		    /* So, do we have any papers? */
		    $page = $_POST['page'] ?? 0;
		    $student_number = $_POST['student_number'] ?? 0;
		    /* If we get to the end of the student list, we try from the beginning for the next page.  If we wrap again, we have finished. */
		    $wrapped = false;
		    do {
		        if (isset($students[$student_number])) {
		            $student = $students[$student_number++];
    		        $testPages = ScannedTestPage::retrieveByDetails(
    		            [ScannedTestPage::STUDENT_ID, ScannedTestPage::TEST_ID, ScannedTestPage::PAGE_NUM],
    		            [$student->getId(), $test->getId(), $page]);
		        } else {
		            if ($wrapped) {
		                die("No more pages or students");
		            }
		            $wrapped = true;
		            $student_number = 0;
		            $page++;
		        }
		    } while (count($testPages) == 0);
		    $testPage = $testPages[0];
		    echo '<form method="POST" enctype="multipart/form-data">';
            echo '<input type="submit" class="form-control btn btn-warning" value="Do not forget to submit (click me or press Enter)!">';
		    echo '<div><input type="number" name="score">';
		    echo '</div>';
		    echo '<div id="testpage">';
		    //echo "<img class=\"img-fluid\" id=\"testpage\" src=\"data:image/png;base64," . base64_encode($testPage->get(ScannedTestPage::IMAGEDATA)) . "\">";
		    echo '</div>';
		    echo "<input type=\"hidden\" name=\"page\" value=\"$page\">";
            echo "<input type=\"hidden\" name=\"student_number\" value=\"$student_number\">";
            echo "<input type=\"hidden\" name=\"form_serial\" value=\"{$_SESSION['form_serial']}\">\n</form>\n</div>";
		}
?>

<script>
    options = {
    		  width: $('.container')[0].clientWidth,
    		  height: $('.container')[0].clientWidth * 1.414,
    		  color: "red",           // Color for shape and text
    		  type : "text",    // default shape: can be "rectangle", "arrow" or "text"
			  tools: ['undo', 'unselect', 'circle', 'text', 'arrow', 'pen', 'redo'], // Tools
    		  images: ["data:image/png;base64,<?= base64_encode($testPage->get(ScannedTestPage::IMAGEDATA))?>"],          // Array of images path : ["images/image1.png", "images/image2.png"]
    		  linewidth: 2,           // Line width for rectangle and arrow shapes
    		  fontsize: $('.container')[0].clientWidth * 1.414 * 0.033 + "px",       // font size for text
			  lineheight: $('.container')[0].clientWidth * 1.414 * 0.033,
    		  bootstrap: true,       // Bootstrap theme design
    		  position: "top",       // Position of toolbar (available only with bootstrap)
    		  selectEvent: "change", // listened event on .annotate-image-select selector to select active images
    		  unselectTool: false,   // Add a unselect tool button in toolbar (useful in mobile to enable zoom/scroll)
    };
    //options.width = $('#container')[0].clientWidth;
	$(document).ready(function(){
	  $('#testpage').annotate(options);
	});
</script>
</body>
</html>