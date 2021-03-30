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
    
    if (isset($_GET['teaching_group']) && isset($_GET['test']) && isset($_GET['download'])) {
        $scannedTests = [];
        foreach ($students as $s) {
            $st = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$s->getId(), $test->getId()]);
            if (count($st) > 0) {
                array_push($scannedTests, $st[0]);
            }
        }
        if (count($scannedTests) == 0) {
            die("No tests!");
        }
        $blankPage = new \Imagick();
        $blankPage->newImage(210, 297, new \ImagickPixel('white'));
        $blankPage->setImageFormat('jpeg');
        $imgs = [];
        $num = 0;
        foreach ($scannedTests as $st) {
            $totalPages = 0;
            foreach (ScannedTestPage::retrieveByDetail(ScannedTestPage::SCANNEDTEST_ID, $st->getId()) as $page) {
                array_push($imgs, $page->get(ScannedTestPage::SHA));
                $totalPages++;
                $num++;
            }
            while ($num % 4 != 0) {
                array_push($imgs, "blank");
                $totalPages++;
                $num++;
            }
        }
        $groupStapleSize = $totalPages / 4;
        switch ($_GET['download']) {
        case 'zip':
            $num = 0;
            // Handy thing about here is, the garbage collector will have it if it crashes!
            ScannedTestPage::lock(true);
            $tempfile = tempnam(Config::scannedTestPagedir, "zipdownload");
            $zip = new \ZipArchive();
            $zip->open($tempfile, \ZipArchive::CREATE);
            foreach ($imgs as $p) {
                if ($p === "blank") {
                    $zip->addFromString("{$num}.jpg", $blankPage);
                } else {
                    $zip->addFile(Config::scannedTestPagedir . "/$p.jpg", "{$num}.jpg");
                }
                $num++;
            }
            $zip->close();
            
            header('Content-Type: application/zip');
            $length = filesize($tempfile);
            header('Content-Length: ' . $length);
            header("Content-Disposition: attachment; filename=\"{$test->getName()}-{$teachingGroup->getName()}.zip\"");
            echo file_get_contents($tempfile);
            unlink($tempfile);
            ScannedTestPage::unlock();
            die();
        case 'html':
            echo <<<EOF
<!doctype html><html><head>
<style>
.a4halfimg {
  height: 180mm;
}
@media print
{    
    h1 {
        display: none !important;
    }
}
</style>
</head><body>
<h1>Please print this onto A4 landscape, stapling in groups of $groupStapleSize.</h1>
EOF;
            $left = true;
            foreach ($imgs as $img) {
                if ($img === 'blank') {
                    $i = base64_encode($blankPage->getImageBlob());
                    $i = "data:image/jpeg;base64,$i";
                } else {
                    $i = "async/getScannedImage.php?imghash=$img";
                }
                if ($left) {
                    echo "<div>";
                }
                echo "<img class=\"a4halfimg\" src=\"$i\">";
                if (!$left) {
                    echo "</div>\n";
                }
                $left = !$left;
            }
            die();
        }
    }
    
}

?>
<!doctype html>
<html>

<head>
<?php require "bin/head.php"; ?>
<?php if (isset($test)) { ?>
<script src='https://cdn.jsdelivr.net/npm/pdf-lib/dist/pdf-lib.js'></script>
<script src='https://cdn.jsdelivr.net/npm/pdf-lib/dist/pdf-lib.min.js'></script>
<script>

const students = [<?php 
    $scannedTestExists = false;
    foreach ($students as $s) {
        if (count(ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$s->getId(), $test->getId()])) > 0) {
            $scannedTestExists = true;
            echo "{ id: {$s->getId()}, name: '{$s->getName()}'},";
        }
    }
?>];

function notify(msg) {
	$('div#status')[0].innerHTML ='<div class="col-12">' + msg + '</div>';
}

function workingOn(i) {
	for (s of students) {
		if (i-- == 0) {
			msg = "Processing " + s.name + "'s test...";
		}
	}
	if (i == 0) {
		msg = "Completed!";
	}
	notify(msg);
}

async function mergeAllPDFs(urls) {

	/* Thanks dude! https://stackoverflow.com/a/65555135/2457487 */
	
	const pdfDoc = await PDFLib.PDFDocument.create();
	const numDocs = urls.length;
	
	for(var i = 0; i < numDocs; i++) {
		workingOn(i);
		const donorPdfBytes = await fetch(urls[i]).then(res => res.arrayBuffer());
		try {
			const donorPdfDoc = await PDFLib.PDFDocument.load(donorPdfBytes);
			const docLength = donorPdfDoc.getPageCount();
			for(var k = 0; k < docLength; k++) {
				const [donorPage] = await pdfDoc.copyPages(donorPdfDoc, [k]);
				pdfDoc.addPage(donorPage);
			}
		} catch (err) {
			location.replace(urls[i]);
			return;
		}
	}
	
	notify("Merging...");
	
	const pdfDataUri = await pdfDoc.saveAsBase64({ dataUri: true });
	
	var link = document.createElement('a');
	link.download = "<?= "{$test->getName()}-{$teachingGroup->getName()}.pdf"; ?>";
	link.href = pdfDataUri;
	link.click();
	
	notify("Complete.");
}

function pdfmerge_doit(multiple) {
	var urls = [];
	for (s of students) {
		urls.push('async/getScannedTestPdf.php?testId=<?= $test->getId(); ?>&pagesPerSheet=' + multiple + '&studentId=' + s.id);
	}
	mergeAllPDFs(urls);
}

</script>
<?php } /* isset($tests) */ ?>
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
		    if ($scannedTestExists) {
		        echo <<< eof
        <div class="row" id="status">
            <div class="col-5">
                <a class="btn btn-primary" onclick="pdfmerge_doit(4)">Download</a> so I can print 2 per page.
            </div>

            <div class="col-7">
                This pads with blank pages to ensure that title pages land on a 4-page boundary.
                Print using 2 per page, and staple with "x sheets per group", where x is the number of test pages divided by 4.
            </div>
        </div>

        <div class="row">
            <div class="col-5">
                <a class="btn btn-primary" href="?subject={$_GET['subject']}&teaching_group={$_GET['teaching_group']}&test={$_GET['test']}&download=zip">Download</a> as an image zip.
            </div>

            <div class="col-7">
                This pads with blank pages to ensure that title pages land on a 4-page boundary.
                Unzip, and print as photos using 2 per page, and staple with "x sheets per group", where x is the number of test pages divided by 4.
            </div>
        </div>
        
        <div class="row">
            <div class="col-5">
                <a class="btn btn-primary" href="?subject={$_GET['subject']}&teaching_group={$_GET['teaching_group']}&test={$_GET['test']}&download=html">Print</a> in browser.
            </div>

            <div class="col-7">
                This pads with blank pages to ensure that title pages land on a 4-page boundary.
                Print on landscape and staple with "x sheets per group", where x is the number of test pages divided by 4.
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
