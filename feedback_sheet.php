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
$date = date('Y-m-d');

/*
 * Now we need to find the first appropriate target, based on Section B marks.
 *
 * So, we divide the total by the number of targets to get the 'marks per shift'
 *
 * We then shift, except the top three are just the top three.  Clear?  Good.
 */

$test_total = $test->get(Test::TOTAL_A) + $test->get(Test::TOTAL_B);

$marks_to_shift = $test->get(Test::TOTAL_B) / $subject->get(Subject::NUM_TARGETS);

$img = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(Config::site_docroot . "/img/dshs.jpg"));

foreach ($group->getStudents() as $student) {
    $result = $test->getResult($student);
    if (is_null($result)) {
        continue;
    }
    
    $result_total = $result->get(TestResult::SCORE_A) + $result->get(TestResult::SCORE_B);
    
    $targets = $test->get(Test::TARGETS);
    $numtargets = 3;
    $shiftmarks = $result->get(TestResult::SCORE_B);
    
    while (($shiftmarks = $shiftmarks - $marks_to_shift) >= 0) {
        if ($numtargets >= 1) {
            $numtargets--;
            continue;
        }
        array_shift($targets);
    }
    if ($firstPage) {
        $firstPage = false;
        $pagebreak = "";
    } else {
        $pagebreak='style="page-break-before: always"';
    }
    
    echo "<div $pagebreak><img src=\"$img\" style=\"width:30%;\" />&nbsp;</div>";
    
    
    echo "<br /><div><h1>Science Assessment Record & Feedback</h1></div>";
    echo <<< EOF
<table>
    <colgroup>
        <col style="width:33%;">

        <col style="width:33%;">

        <col style="width:34%;">
    </colgroup>

    <tr>
        <td colspan="2">Name: {$student->getName()}</th>

        <td>Teacher: {$_GET['teacher_name']}</th>
    </tr>

    <tr>
        <td>Indicative grade range: <strong>{$student->getBaseline($subject)}</strong></td>

        <td>Most likely grade: <strong>{$student->getMostLikelyGrade($subject)}</strong></td>

        <td>Personal target grade:</td>
    </tr>

    <tr>
        <td colspan="3">&nbsp;</td>
    <tr>

    <tr>
        <td colspan="2">Assessment title: <strong>{$test->getName()}</strong></td>

        <td>Date: $date</td>
    </tr>

    <tr>
        <td>Grade achieved: {$test->calculateGrade($result)}</td>

        <td>Marks achieved: $result_total</td>

        <td>Marks available: $test_total</td>
    </tr>

    <tr>
        <td colspan=3>
            <div>Targets for improvement are:</div>

            <ol>
                <li>$targets[0]</li>
            
                <li>$targets[1]</li>
            
                <li>$targets[2]</li>
            </ol>
        </td>
    </tr>

    <tr>
        <td colspan="3">Evidence to show that targets have been met (teacher will sign below):</td>
    </tr>
</table>
EOF;
}
?>
		</div>
	</body>
</html>