<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_GET['newtest-name'])) {
    if (!empty($_GET['newtest-name'])) {
        $newTestDetails = [];
        foreach ($_GET as $k => $v) {
            if (str_contains($k, "newtest-")) {
                $k = str_replace('newtest-', '', $k);
                $newTestDetails[$k] = $v;
            }
        }
        $t = new Test($newTestDetails);
        $t->commit();
    }
    
    /* TODO This doesn't work yet
    
    foreach ($_GET as $k => $v) {
        if (!empty($v)) {
            if (str_contains($k, "subject-add-group-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-add-group-", "", $k))[0];
                $subject->addMember(TeachingGroup::retrieveByDetail(TeachingGroup::ID, $v)[0]);
            }
        }
    }
    
    */
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
    echo "<tr><td><select name=\"subject-$tId\">";
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
    View::makeTextBoxCell("name-$tId", $t->get(Test::NAME));
    // TODO Topic
    echo "<td>&nbsp;</td>";
    // Total score
    View::makeTextBoxCell("total-$tId", $t->get(Test::TOTAL));
    // Custom grade boundaries?
    if ($t->get(Test::CUSTOM_GRADE_BOUNDARIES)) {
        $checked = "checked";
    } else {
        $checked = "";
    }
    echo "<td><div class=\"custom-control custom-checkbox\">";
    echo "<input type=\"checkbox\" class=\"custom-control-input\" id=\"custom-$tId\" name=\"custom-$tId\" $checked>";
    echo "<label class=\"custom-control-label\" for=\"custom-$tId\">&nbsp;&nbsp;</label>";
    echo "</div></td>";
    for ($i = 1; $i <= 9; $i++) {
        View::makeTextBoxCell("grade-$i-$tId", $t->getGradeBoundary($i));
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
	View::makeTextBoxCell("newtest-" . Test::NAME, "");

	// Topics not implemented yet
	echo "<td>&nbsp;</td>";
	
	View::makeTextBoxCell("newtest-" . Test::TOTAL, "");
	
	?>

    <td>
    	<div class="custom-control custom-checkbox">
    		<input type="checkbox" class="custom-control-input" name="newtest-<?= Test::CUSTOM_GRADE_BOUNDARIES ?>" id="custom-newtest">
    		<label class="custom-control-label" for="custom-newtest">&nbsp;&nbsp;</label>
    	</div>
    </td>
	
    <?php
    for ($i = 1; $i <= 9; $i++) {
        View::makeTextBoxCell("newtest-" . Test::GRADE . $i, "");
    }
	?>
</tr>

</table>
<input type="submit" class="form-control" value="Save">
</form>
</div>
</body>
</html>