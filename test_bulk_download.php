<?php
namespace TestAnalysis;

$pageTitle = "Download marked tests in bulk";

require "bin/classes.php";
require "dev/upgrade_database.php";

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
        $teachingGroup = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['teaching_group'])[0];
        $students = $teachingGroup->getStudents();
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
    
    if (isset($_GET['download']) && $_GET['download'] == "yes" && isset($test)) {
        $pages = [];
        
        $blankPage = new \Imagick();
        $blankPage->newImage(210, 297, new \ImagickPixel('white'));
        $blankPage->setImageFormat('jpeg');
        
        $pdf = new \Imagick();
        $pdf->setresolution(150, 150);
        
        foreach ($students as $s) {
            $st = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$s->getId(), $test->getId()]);
            if (isset($st[1]))
                die("Something is wrong; {$s->getName()} appears to have multiple scanned versions of {$test->getName()}.");
            if (isset($st[0])) {
                $stps = $st[0]->getPages();
                $cnt = 0;
                while (isset($stps[$cnt])) {
                    $pdf->readimageblob($stps[$cnt++]->get(ScannedTestPage::IMAGEDATA));
                    $pdf->scaleimage(0, 1700);
                    $pdf->setImageFormat('pdf');
                }
                while ($cnt++ % 4 != 0) {
                    $pdf->readimageblob($blankPage);
                    $pdf->scaleimage(0, 1700);
                    $pdf->setImageFormat('pdf');
                }
            }
        }
        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename={$test->getName()}-{$teachingGroup->getName()}.pdf");
        echo $pdf->getimagesblob();
        die();
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
		    $scannedTestExists = false;
		    foreach ($students as $s) {
		        if (count(ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$s->getId(), $test->getId()])) > 0) {
		            $scannedTestExists = true;
		            break;
		        }
		    }
		    if ($scannedTestExists) {
		        echo <<< eof
        <div class="row">
            <div class="col-5">
                <a href="?teaching_group={$_GET['teaching_group']}&subject={$_GET['subject']}&test={$_GET['test']}&download=yes">Download so that I can print 2 per page, double sided</a>.
            </div>

            <div class="col-7">
                This pads with blank pages to ensure that title pages land on a 4-page boundary.
                Print using 2 per page, and staple with "x sheets per group", where x is the number of test pages divided by 4.
            </div>
        </div>
    </div>
</body>
</html>
eof;
		    } else {
		        echo "<div>No tests found.</div></div></body></html>";
		    }
		}
