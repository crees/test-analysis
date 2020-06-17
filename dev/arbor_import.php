<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_GET['removeGroup'])) {
    Subject::retrieveByDetail(Subject::ID, $_GET['removeFromSubject'])[0]->removeMember(TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['removeGroup'])[0]);
} elseif (isset($_GET['newsubjectcode'])) {
    if (!empty($_GET['newsubjectname'])) {
        $s = new Subject([
            Subject::NAME => $_GET['newsubjectname'],
            Subject::CODE => $_GET['newsubjectcode'],
        ]);
        $s->commit();
    }
    
    foreach ($_GET as $k => $value) {
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
<form method="get">
<table class="table">
<thead><tr><th>Subject code</th><th>Subject name</th><th>Groups (click to remove)</th><th>Add group</th></tr></thead>
<?php
$orphanedGroups = TeachingGroup::retrieveAll();
foreach (Subject::retrieveAll(Subject::NAME) as $s) {
    $allGroups = TeachingGroup::retrieveAll();
    echo "<tr><td>" . $s->get(Subject::CODE) . "</td><td>" . $s->get(Subject::NAME) . "</td>";
    $names = [];
    foreach ($s->getTeachingGroups() as $g) {
        array_push($names, "<a href=\"?removeGroup=" . $g->getId() . "&removeFromSubject=" . $s->getId() . "\">" . $g->getName() . "</a>");
        unset($allGroups[array_search($g, $allGroups)]);
        if ($o = array_search($g, $orphanedGroups)) {
            unset($orphanedGroups[$o]);
        }
    }
    echo "<td>" . implode(", ", $names) . "</td>";
    
    echo "<td><select name=\"subject-add-group-" . $s->getId() . "\" onchange=\"this.form.submit()\">";
    echo "<option value=\"\" selected>Add Group to " . $s->getName() . "</option>";
    foreach ($allGroups as $g) {
        echo "<option value=\"" . $g->getId() . "\">" . $g->getName() . "</option>";
    }
    echo "</select></td>";
    echo "</tr>";
}
?>
<tr>
	<td><input class="form-control" type="text" name="newsubjectcode"></td>

	<td><input class="form-control" type="text" name="newsubjectname"></td>

	<td><input class="form-control" type="submit" value="Add subject"></td>
</tr>
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