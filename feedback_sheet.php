<?php
namespace TestAnalysis;

require "bin/classes.php";

if (!isset($_GET['teaching_group']) || !isset($_GET['subject']) || !isset($_GET['test'])) {
    header('Location: index.php');
}

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
$subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
$group = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['teaching_group'])[0];
$test = Test::retrieveByDetail(Test::ID, $_GET['test'])[0];
$firstPage = true;
$date = date('Y-m-d');

/*
 * Now we need to find the first appropriate target.
 *
 * So, we divide the total by the number of targets to get the 'marks per shift'
 *
 * We then shift, except the top three are just the top three.  Clear?  Good.
 */

$test_total = $test->get(Test::TOTAL_A) + $test->get(Test::TOTAL_B);

$marks_to_shift = $test_total / $subject->get(Subject::NUM_TARGETS);

foreach ($group->getStudents() as $student) {
    $result = $test->getResult($student);
    if (is_null($result)) {
        continue;
    }
    
    $result_total = $result->get(TestResult::SCORE_A) + $result->get(TestResult::SCORE_B);
    
    $targets = $test->get(Test::TARGETS);
    $numtargets = 3;
    $shiftmarks = $result_total;
    
    while (($shiftmarks = $shiftmarks - $marks_to_shift) > 0) {
        if ($numtargets >= 0) {
            $numtargets--;
            continue;
        }
        array_shift($targets);
    }
    if ($firstPage) {
        $firstPage = false;
        echo "<div>&nbsp;</div>";
    } else {
        echo "<div style=\"page-break-before: always\">&nbsp;</div>";
    }
    
    echo "<div><img src=\"img/dshs.jpg\" style=\"width:30%;\" /></div>";
    echo "<div class=\"h3\">Science Assessment Record & Feedback</div>";
    echo <<< EOF
<table class="table table-bordered table-sm">
    <colgroup>
        <col style="width:33%;">

        <col style="width:33%;">

        <col style="width:34%;">
    </colgroup>

    <thead>
        <tr>
            <th colspan="2">Name: {$student->getName()}</th>

            <th>Teacher:</th>
        </tr>
    </thead>

    <tr>
        <td>Indicative grade range: <strong>{$student->getBaseline($subject)}</strong></td>

        <td>Most likely grade:</td>

        <td>Personal target grade:</td>
    </tr>

    <tr>
        <td colspan="2">Assessment title: {$test->getName()}</td>

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