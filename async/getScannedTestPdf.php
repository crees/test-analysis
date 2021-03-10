<?php
namespace TestAnalysis;

$testId = $_GET['testId'];
$studentId = $_GET['studentId'];
$pagesPerSheet = $_GET['pagesPerSheet'] ?? 1;

$students_allowed = true;

require "../bin/classes.php";

$test = Test::retrieveByDetail(Test::ID, $testId)[0];
$student = Student::retrieveByDetail(Student::ID, $studentId)[0];

if (Config::is_student($auth_user)) {
    // Check that it really is that student
    if ($student->get(Student::USERNAME) != $auth_user) {
        die ("Are you trying to get another kid's test?");
    }
}


$st = ScannedTest::retrieveByDetails([ScannedTest::STUDENT_ID, ScannedTest::TEST_ID], [$studentId, $testId]);

if (isset($st[1])) {
    die("Something is wrong; {$student->getName()} appears to have multiple scanned versions of {$test->getName()}.");
}
if (isset($st[0])) {
    $blankPage = new \Imagick();
    $blankPage->newImage(210, 297, new \ImagickPixel('white'));
    $blankPage->setImageFormat('jpeg');
    
    $pdf = new \Imagick();
    $pdf->setresolution(150, 150);
    
    $stps = $st[0]->getPages();
    $cnt = 0;
    while (isset($stps[$cnt])) {
        $pdf->readimageblob($stps[$cnt++]->get(ScannedTestPage::IMAGEDATA));
        $pdf->scaleimage(0, 1700);
        $pdf->setImageFormat('pdf');
    }
    while ($cnt++ % $pagesPerSheet != 0) {
        $pdf->readimageblob($blankPage);
        $pdf->scaleimage(0, 1700);
        $pdf->setImageFormat('pdf');
    }
}

header("Content-type:application/pdf");
header("Content-Disposition:attachment;filename={$test->getName()}-{$student->getName()}.pdf");
echo $pdf->getimagesblob();

