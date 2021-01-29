<?php
namespace TestAnalysis;

$pageTitle = "Upload tests";

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
    
    if (isset($_POST['form_serial']) && (session_status() != PHP_SESSION_ACTIVE || $_POST['form_serial'] != $_SESSION['form_serial'] - 1)) {
        foreach ($students as $s) {
            if (isset($_POST["delete-for-{$s->getId()}"]) && $_POST["delete-for-{$s->getId()}"] == 'on') {
                foreach (ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$s->getId(), $test->getId()]) as $st) {
                    foreach ($st->getPages() as $p) {
                        ScannedTestPage::delete($p->getId());
                    }
                    ScannedTest::delete($st->getId());
                }
            }
        }
        if (isset($_FILES["input-file"])) {
            $f = $_FILES["input-file"];
            $pages = [];
            if ($f['size'] > 0) {
                switch (substr($f['name'], -4, 4)) {
                case ".pdf":
                    try {
                        if (defined('TestAnalysis\Config::windows_path_to_gs_exe')) {
                            shell_exec(Config::windows_path_to_gs_exe . " -sDEVICE=jpeg -sOutputFile={$f['tmp_name']}-page-%03d.jpg -r150x150 -f -dBATCH -dNOPAUSE -q {$f['tmp_name']}");
                            $pages = [];
                            foreach (glob("{$f['tmp_name']}-page-[0-9][0-9][0-9].jpg") as $page) {
                                array_push($pages, addslashes(file_get_contents($page)));
                                unlink($page);
                            }
                        } else {
                            $im = new \Imagick();
                            $im->setresolution(150, 150);
                            $im->readimage($f['tmp_name']);
                            for ($i = 0; $i < $im->getnumberimages(); $i++) {
                                $im->setiteratorindex($i);
                                $im->setimageformat('jpg');
                                $im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                                array_push($pages, addslashes($im->getimageblob()));
                            }
                            $im->destroy();
                        }
                    } catch (\ImagickException $e) {
                        die('Well, that\'s a shame.  For some reason, we can\'t extract pdf files, so please use <a href="https://www.ilovepdf.com/pdf_to_jpg">a pdf converter</a> to turn your pdf into a zipfile of images and try uploading that.');
                    }
                    break;
                case ".zip":
                    /* Deal with zipped images in alphabetical order.  Very primitive :( */
                    $zip = new \ZipArchive();
                    $zip->open($f['tmp_name']);
                    $zipcontents = [];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $n = $zip->getNameIndex($i);
                        if (preg_match('/[.]jpe?g$/', $n) == 1) {
                            array_push($zipcontents, $n);
                        }
                    }
                    sort($zipcontents);
                    foreach ($zipcontents as $name) {
                        array_push($pages, addslashes($zip->getFromName($name)));
                    }
                    break;
                default:
                    die("Sorry, only pdfs or zips are accepted");
                    break;
                }

                unlink($f['tmp_name']);
                
                $components = $test->getTestComponents();
                
                $selfUpload = isset($_POST['student_upload_allowed']) ? true : false;
                
                foreach ($students as $s) {
                    if (isset($_POST["set-for-{$s->getId()}"])) {
                        $scannedTest = new ScannedTest([
                            ScannedTest::TEST_ID => $test->getId(),
                            ScannedTest::STUDENT_ID => $s->getId(),
                            ScannedTest::SUBJECT_ID => $subject->getId(),
                            ScannedTest::MINUTES_ALLOWED => $_POST['test_time'] + $_POST["input-minutes-{$s->getId()}"],
                            ScannedTest::TS_UNLOCKED => strtotime($_POST['unlock_date']),
                            ScannedTest::STAFF_ID => $staff->getId(),
                            ScannedTest::STUDENT_UPLOAD_ALLOWED => $selfUpload,
                            ]);
                        $scannedTest->commit();
                        $currentComponentIndex = 0;
                        foreach ($pages as $num => $p) {
                            // XXX Yes, this next bit is horrible, I'm sorry
                            //     Fun exercise for the reader?
                            if (isset($components[$currentComponentIndex+1]) && 
                                $_POST["page-for-component-{$components[$currentComponentIndex+1]->getId()}"] == $num+1
                            ) {
                                $currentComponentIndex++;
                            }
                            $page = new ScannedTestPage([
                                ScannedTestPage::SCANNEDTEST_ID => $scannedTest->getId(),
                                ScannedTestPage::TESTCOMPONENT_ID => $components[$currentComponentIndex]->getId(),
                                ScannedTestPage::PAGE_NUM => $num,
                                ScannedTestPage::IMAGEDATA => $p,
                            ]);
                            $page->commit();
                        }   
                    }
                }
            }
            header("Location: ?subject={$_GET['subject']}&teaching_group={$_GET['teaching_group']}&test={$_GET['test']}");
            die();
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
		    echo <<< eof
        <form method="POST" enctype="multipart/form-data">
            <input type="submit" class="form-control btn btn-warning" value="Submit (click me or press Enter)!">
            <div class="form-group">
		        <label class="form-label" for="input-file">Test file to upload (jpgs in zip or pdf)</label>
                <input type="file" class="form-control-file" name="input-file" id="input-file">
            </div>
eof;
		    $components = $test->getTestComponents();
		    $firstComponent = array_shift($components);
		    echo "<input type=\"hidden\" name=\"page-for-component-{$firstComponent->getId()}\" value=\"0\" />";
		    if (isset($components[0])) {
		        // More than one component, so let's ask which page each one starts on
    		    foreach ($components as $c) {
    		        echo "<input class=\"form-control\" type=\"number\" name=\"page-for-component-{$c->getId()}\" placeholder=\"Section {$c->getName()} begins on page...\">";
    		    }
		    }
            echo <<< eof
            <div class="form-group">
		        <label class="form-label" for="test_time">Time for test (minutes)</label>
                <input class="form-control" type="number" id="test_time" name="test_time">
            </div>
            <div class="form-group">
		        <label class="form-label" for="unlock_date">Date to unlock test (optional)</label>
                <input class="form-control" type="date" id="unlock_date" name="unlock_date">
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="student_upload_allowed" name="student_upload_allowed">
		        <label class="form-label" for="student_upload_allowed">Allow students to upload their own version (advanced, suggest only for sixth form as they can defeat the timer)</label>
            </div>
eof;
		    echo <<< eof
            <div class="table-responsive table-95 table-stickyrow">
            <table class="table table-bordered table-sm table-hover">
                <thead>
                    <tr>
                        <th rowspan="2" scope="col">Name</th>
                        <th rowspan="2" scope="col">Group</th>
                        <th rowspan="2" scope="col">Assign to this student</th>
                        <th rowspan="2" scope="col">Extra time for test (+/- minutes allowed)</th>
                    </tr>
                </thead>
eof;
		    foreach ($students as $s) {
		        echo "<tr>";
		        echo "<td>" . $s->getName() . "</td>";
		        echo "<td>" . $s->getTeachingGroup($subject) . "</td>";
		        if (!empty(ScannedTest::retrieveByDetails([ScannedTest::TEST_ID, ScannedTest::STUDENT_ID], [$test->getId(), $s->getId()]))) {
		            echo "<td class=\"text-success\">Assigned successfully</td><td><div class=\"form-check text-danger\"><input class=\"form-check-input\" type=\"checkbox\" name=\"delete-for-" . $s->getId() . "\" id=\"delete-for-" . $s->getId() . "\">";
		            echo "<label class=\"form-check-label\" for=\"delete-for-" . $s->getId() . "\">Delete uploaded {$test->getName()}</label><div></td>";
		        } else {
		            echo "<td><div class=\"form-check\"><input type=\"checkbox\" class=\"form-check-input\" name=\"set-for-" . $s->getId() . "\" checked></div></td>";
		            echo "<td><input type=\"number\" class=\"form-control-input\" name=\"input-minutes-{$s->getId()}\" value=\"0\"></td>";
		        }
		        echo "</tr>\n";
		    }
		    echo "</table>\n</div><input type=\"hidden\" name=\"form_serial\" value=\"{$_SESSION['form_serial']}\">\n</form>\n</div>\n</body>\n</html>";
		}
