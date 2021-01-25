<?php
namespace TestAnalysis;

require "../bin/classes.php";

$student = Student::retrieveByDetail(Student::USERNAME, $auth_user);

if (!isset($_GET['subject']) || !isset($_GET['test']) || !isset($student[0]) || $_GET['student'] != $student[0]->getId()) {
    // Nice try, trying to hack another kid's results!
    header('Location: index.php');
    die();
}

$student = $student[0];

$subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
$test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
$student = Student::retrieveByDetail(Student::ID, $_GET['student'])[0];

// First retrieve the template Subject file
$template = new TempFile("feedback-template-");
$myfile = new TempFile("feedback-{$student->getId()}-");

$fbsheettemplate = $subject->getFeedbackSheetTemplate();

if (is_null($fbsheettemplate)) {
    die("No feedback sheet template for your subject.");
}

file_put_contents($template->getPath(), $fbsheettemplate->get(FeedbackSheet::TEMPLATEDATA));

$dm = new \DocxMerge\DocxMerge();

$dm->setValues($template->getPath(), $myfile->getPath(), FeedbackSheet::getSubst($subject, $test, $student));

header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("content-disposition: attachment;filename=\"feedback-{$test->getName()}-{$student->getName()}.docx\"");

die(file_get_contents($myfile->getPath()));

