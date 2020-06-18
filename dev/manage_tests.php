<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_GET['newtest-name'])) {
    foreach (Test::retrieveAll() as $t) {
        $tId = $t->getId();
        if (isset($_GET[Test::SUBJECT_ID . "-$tId"]) && !empty($_GET[Test::SUBJECT_ID . "-$tId"])) {
            $detail = [];
            $detail[Test::ID] = $tId;
            $detail[Test::SUBJECT_ID] = $_GET[Test::SUBJECT_ID . "-$tId"];
            $detail[Test::NAME] = $_GET[Test::NAME . "-$tId"];
            // $t->set(Test::TOPIC, $_GET[Test::TOPIC . "-$tId"]);
            $detail[Test::TOTAL] = $_GET[Test::TOTAL . "-$tId"];
            if (isset($_GET[Test::CUSTOM_GRADE_BOUNDARIES . "-$tId"])) {
                $detail[Test::CUSTOM_GRADE_BOUNDARIES] = 1;
            } else {
                $detail[Test::CUSTOM_GRADE_BOUNDARIES] = 0;
            }
            for ($i = 1; $i <= 9; $i++) {
                $detail[Test::GRADE . $i] = $_GET[Test::GRADE . $i . "-$tId"];
            }
            
            $newTest = new Test($detail);
            
            $newTest->commit();
        }
    }
    
    if (!empty($_GET['newtest-name'])) {
        $newTestDetails = [];
        foreach ($_GET as $k => $v) {
            if (str_contains($k, "newtest-")) {
                $k = str_replace('newtest-', '', $k);
                if ($k == Test::CUSTOM_GRADE_BOUNDARIES) {
                    $newTestDetails[$k] = 1;
                } else {
                    $newTestDetails[$k] = $v;
                }
            }
        }
        $t = new Test($newTestDetails);
        $t->commit();
    }
}
?>
<!doctype html>
<html><head><?php require "../bin/head.php" ?></head>
<body>
<div class="container">
<div class="row"><a href=".." class="button" role="button">Home</a></div>
<form method="get">
<table class="table table-hover table-bordered table-sm">
<thead>
	<tr>
		<th>Subject</th>
		<th>Test name</th>
		<th>Topic<!-- TODO --></th>
		<th>Total score</th>
		<th>Nonstandard grades?</th>
<?php
for ($i = 1; $i <= 9; $i++) {
    echo "      <th>$i</th>\n";
}
?>
	</tr>
</thead>

<?php
$subjects = Subject::retrieveAll(Subject::NAME);
foreach (Test::retrieveAll(Test::NAME) as $t) {
    $tId = $t->getId();
    // Subject
    echo "<tr><td><select name=\"" . Test::SUBJECT_ID . "-$tId\">";
    foreach ($subjects as $s) {
        if ($s->getId() == $t->get(Test::SUBJECT_ID)) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        echo "<option value=\"" . $s->getId() . "\" $selected>" . $s->getName() . "</option>";
    }
    echo "</select></td>";    
    // Test name
    echo View::makeTextBoxCell(Test::NAME . "-$tId", $t->get(Test::NAME));
    // TODO Topic
    echo "<td>&nbsp;</td>";
    // Total score
    echo View::makeTextBoxCell(Test::TOTAL . "-$tId", $t->get(Test::TOTAL));
    // Custom grade boundaries?
    if ($t->get(Test::CUSTOM_GRADE_BOUNDARIES)) {
        $checked = "checked";
    } else {
        $checked = "";
    }
    echo "<td><div class=\"custom-control custom-checkbox\">";
    echo "<input type=\"checkbox\" class=\"custom-control-input\" id=\"custom-$tId\" name=\"" . Test::CUSTOM_GRADE_BOUNDARIES . "-$tId\" $checked>";
    echo "<label class=\"custom-control-label\" for=\"custom-$tId\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>";
    echo "</div></td>";
    for ($i = 1; $i <= 9; $i++) {
        echo View::makeTextBoxCell("grade$i-$tId", $t->getGradeBoundary($i));
    }
    echo "</tr>";
}
?>
<tr>
	<td>
		<select name="newtest-<?= Test::SUBJECT_ID?>">
	    <?php 
	    foreach ($subjects as $s) {
            echo "<option value=\"" . $s->getId() . "\">" . $s->getName() . "</option>";
        }
        ?>
		</select>
	</td>
	<?php
	echo View::makeTextBoxCell("newtest-" . Test::NAME, "");

	// Topics not implemented yet
	echo "<td>&nbsp;</td>";
	
	echo View::makeTextBoxCell("newtest-" . Test::TOTAL, "");
	
	?>

    <td>
    	<div class="custom-control custom-checkbox">
    		<input type="checkbox" class="custom-control-input" name="newtest-<?= Test::CUSTOM_GRADE_BOUNDARIES ?>" id="custom-newtest">
    		<label class="custom-control-label" for="custom-newtest">&nbsp;&nbsp;</label>
    	</div>
    </td>
	
    <?php
    for ($i = 1; $i <= 9; $i++) {
        echo View::makeTextBoxCell("newtest-" . Test::GRADE . $i, "");
    }
	?>
</tr>

</table>
<input type="submit" class="form-control" value="Save">
</form>
</div>
</body>
</html>