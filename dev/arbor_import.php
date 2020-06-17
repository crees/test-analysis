<?php
namespace TestAnalysis;

include "../bin/classes.php";
?>
<!doctype html>
<html><head><?php require "../bin/head.php" ?></head>
<body>
<div class="container">
<?php

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
        echo "<div class=\"row\">Scanned TeachingGroup: " . $dGroup->getName() . "</div>"; 
    } else {
        $group['name'] = $group['displayName'];
        $dGroup = new TeachingGroup($group);
        echo "<div class=\"row\">New TeachingGroup: " . $dGroup->getName() . "</div>";
    }
    $dGroup->commit();
    
    foreach ($group['memberships'] as $membership) {
        if (!empty($dStudent = Student::retrieveByDetail(Student::ID, $membership['student']['id']))) {
            $dStudent = $dStudent[0];
            $dStudent->setNames($membership['student']['firstName'], $membership['student']['lastName']);
            echo "<div class=\"row\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Scanned Student: " . $dStudent->getName();
        } else {
            $dStudent = new Student([
                Student::FIRST_NAME => $membership['student']['firstName'],
                Student::LAST_NAME  => $membership['student']['lastName'],
                Student::ID         => $membership['student']['id']
            ]);
            echo "<div class=\"row\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;New Student: " . $dStudent->getName();
        }
        $dGroup->addMember($dStudent);
        
        $dStudent->commit();
        
        echo "... added to db and membership made</div>";
    }
}
?>

</div>
</body>
</html>