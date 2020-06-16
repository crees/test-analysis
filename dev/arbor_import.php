<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_POST['newsubjectcode'])) {
    if (!empty($_POST['newsubjectname'])) {
        $s = new Subject([
            Subject::NAME => $_POST['newsubjectname'],
            Subject::CODE => $_POST['newsubjectcode'],
        ]);
        $s->commit();
    }
    
    foreach ($_POST as $k => $value) {
        if (!empty($value)) {
            if (str_contains($k, "subject-add-group-")) {
                $subject = Subject::retrieveByDetail(Subject::ID, str_replace("subject-add-group-", "", $k))[0];
                $subject->addMember(TeachingGroup::retrieveByDetail(TeachingGroup::ID, $value)[0]);
            }
        }
    }
} else {
    $client = new GraphQLClient();
    
    $data = $client->rawQuery("query {
      TeachingGroup (academicYear__code: \"2019-2020\") {
        displayName
        memberships {
          student {
            lastName
            firstName
            id
          }
        }
      }
    }")->getData();
    
    foreach ($data['TeachingGroup'] as $group) {
        if (!empty($dGroup = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $group['id']))) {
            $dGroup = $dGroup[0];
            $dGroup->setName($group['displayName']);
        } else {
            $group['name'] = $group['displayName'];
            $dGroup = new TeachingGroup($group);
        }
        $dGroup->commit();
        
        foreach ($group['memberships'] as $membership) {
            if (!empty($dStudent = Student::retrieveByDetail(Student::ID, $membership['student']['id']))) {
                $dStudent = $dStudent[0];
                $dStudent->setNames($membership['student']['firstName'], $membership['student']['lastName']);
            } else {
                $dStudent = new Student([
                    Student::FIRST_NAME => $membership['student']['firstName'],
                    Student::LAST_NAME  => $membership['student']['lastName'],
                    Student::ID         => $membership['student']['id']
                ]);
            }
            $dGroup->addMember($dStudent);
            
            $dStudent->commit();
        }
    }
}
?>
<!doctype html>
<html><head><?php require "../bin/head.php" ?></head>
<body>
<div class="container">
<form method="post">
<table class="table">
<thead><tr><th>Subject code</th><th>Subject name</th><th>Groups...</th></tr></thead>
<?php
$orphanedGroups = TeachingGroup::retrieveAll();
foreach (Subject::retrieveAll(Subject::NAME) as $s) {
    $allGroups = TeachingGroup::retrieveAll();
    echo "<tr><td>" . $s->get(Subject::CODE) . "</td><td>" . $s->get(Subject::NAME) . "</td>";
    foreach ($s->getTeachingGroups() as $g) {
        echo "<td>" . $g->getName() . "</td>";
        unset($allGroups[array_search($g, $allGroups)]);
        if (isset($orphanedGroups[array_search($g, $allGroups)])) {
            unset($orphanedGroups[array_search($g, $allGroups)]);
        }
    }
    echo "<td><select name=\"subject-add-group-" . $s->getId() . "\" onchange=\"this.form.submit()\">";
    echo "<option value=\"\" selected>Add Group to Subject</option>";
    foreach ($allGroups as $g) {
        echo "<option value=\"" . $g->getId() . "\">" . $g->getName() . "</option>";
    }
    echo "</select></td>";
    echo "</tr>";
}
?>
<tr>
	<td><input type="text" name="newsubjectcode"></td>
	<td><input type="text" name="newsubjectname"></td>
</tr>
<tr><td>&nbsp;</td><td><input type="submit" value="Add subject"></td></tr>
</table>
</form>
<?php
if (!empty($orphanedGroups)) {
    echo "<div>Groups not assigned to any subject:</div>";

    foreach ($orphanedGroups as $g) {
        echo "<div>" . $g->getName() . "</div>";
    }
}
?>
</div>
</body>
</html>