<?php
namespace TestAnalysis;

require "bin/classes.php";

if (!isset($_GET['teaching_group']) || !isset($_GET['subject']) || !isset($_GET['test'])) {
    header('Location: index.php');
}

if (!isset($_GET['teacher_name'])) {
    echo "<!doctype html><head>";
    require "bin/head.php";
    echo <<< EOF
    </head>

    <body>
        <div class="container">
    		<div class="d-print-none h2"><a href="index.php?subject={$_GET['subject']}&teaching_group={$_GET['teaching_group']}">Back to database</a></div>

            <div class="h2">Please fill in the missing information for the yellow sheet.</div>
            <form method="get">
                <input type="hidden" name="teaching_group" value="{$_GET['teaching_group']}">

                <input type="hidden" name="subject" value="{$_GET['subject']}">

                <input type="hidden" name="test" value="{$_GET['test']}">

        		<div class="form-group row">
        			<label for="teacher_name" class="col-2 col-form-label">Teacher name</label>

          			<div class="col-10">
                        <input type="text" id="teacher_name" name="teacher_name">
                    </div>
                </div>
                <div class="form-group row">
                    <input type="submit" value="Make the sheets!">
                </div>
            </form>
        </div>
    </body>
</html>
EOF;
    die();
}

$subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
$group = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['teaching_group'])[0];
$test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];

header("Content-Type: application/vnd.ms-word");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("content-disposition: attachment;filename=\"feedback-{$test->getName()}-{$group->getName()}.doc\"");

?>
<!doctype html>
<html>
    <head>
    	<?php require "bin/head.php"; ?>
    	
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
$firstPage = true;
foreach ($group->getStudents() as $student) {
    $sheet = new FeedbackSheet($subject, $test, $student, $_GET['teacher_name']);
    $sheet->draw($firstPage);
    $firstPage = false;
}
?>
		</div>
	</body>
</html>