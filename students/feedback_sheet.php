<?php
namespace TestAnalysis;

require "../bin/classes.php";

if (!isset($_GET['subject']) || !isset($_GET['test']) || !isset($_SESSION['student_id']) || $_GET['student'] != $_SESSION['student_id']) {
    // Nice try, trying to hack another kid's results!
    header('Location: index.php');
    die();
}

$subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
$test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
$student = Student::retrieveByDetail(Student::ID, $_GET['student'])[0];

header("Content-Type: application/vnd.ms-word");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("content-disposition: attachment;filename=\"feedback-{$test->getName()}-{$student->getName()}.doc\"");

?>
<!doctype html>
<html>
    <head>
    	<?php require "../bin/head.php"; ?>
    	
    	<style type="text/css" media="print">
            @page 
            {
                size: auto;   /* auto is the initial value */
                margin: 0mm;  /* this affects the margin in the printer settings */
            }
        </style>
    </head>
    
    <body>
    	<div class="container">
<?php
$sheet = new FeedbackSheet($subject, $test, $student);
$sheet->draw(true);
?>
		</div>
	</body>
</html>