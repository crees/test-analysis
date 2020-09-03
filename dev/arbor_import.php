<?php
namespace TestAnalysis;

include "../bin/classes.php";
?>
<!doctype html>
<html>
<head><?php require "../bin/head.php" ?></head>
<body>
	<div class="container">
        <nav class="navbar navbar-expand">
            <!-- Brand -->
            <a class="navbar-brand">Navigation</a>
            
            <!-- Toggler/collapsibe Button -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
            	<span class="navbar-toggler-icon">collapse</span>
            </button>
            
            <!-- Navbar links -->
            <div class="collapse navbar-collapse" id="collapsibleNavbar">
            	<ul class="navbar-nav">
            		<li class="nav-item">
                		<a class="nav-link" href="../">Home</a>
                	</li>
                	<li class="nav-item">
                		<a class="nav-link" href="index.php">Database management</a>
                	</li>
            	</ul>
        	</div>
        </nav>
<?php

$client = new GraphQLClient();

if (empty($_GET['baseline_done'])) {
    $page_num = 0;
    
    while (!empty(($data = $client->rawQuery("query {
      StudentProgressBaseline (page_num: $page_num) {
        id
        student {
          id
        }
        assessment {
          id
          displayName
          subject {
            subjectName
          }
        }
        grade {
          displayName
        }
      }
    }")->getData())['StudentProgressBaseline'])) {
        foreach ($data['StudentProgressBaseline'] as $baseline) {
            $details = [];
            // Does this Baseline already exist?  Overwrite if so.
            $details[Baseline::ID] = $baseline['id'];
            $details[Baseline::GRADE] = $baseline['grade']['displayName'];
            $details[Baseline::STUDENT_ID] = $baseline['student']['id'];
            $details[Baseline::NAME] = $baseline['assessment']['displayName'];
            $details[Baseline::MIS_ASSESSMENT_ID] = $baseline['assessment']['id'];
            $dBaseline = new Baseline($details);
            $dBaseline->commit();
        }
        $page_num += 1;
    }
    
    die('<a href="?baseline_done=yes" class="btn btn-primary">Baselines done!  Now click to import groups</a>');
}

$ay = Config::academic_year;

$query = "query {
  TeachingGroup (academicYear__code: \"$ay\") {
    displayName
    memberships {
      student {
        lastName
        firstName
        id
      }
    }
  }
}";

$data = $client->rawQuery($query)->getData();
  
// Clear old memberships out
(new Database())->dosql("DELETE FROM studentgroupmembership;");

foreach ($data['TeachingGroup'] as $group) {
    if (!empty($dGroup = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $group['id']))) {
        $dGroup = $dGroup[0];
        $dGroup->setName($group['displayName']);
        echo "<div class=\"row\">Scanned TeachingGroup: " . $dGroup->getName() . "</div>"; 
    } else {
        $group[TeachingGroup::NAME] = $group['displayName'];
        $group[TeachingGroup::ACADEMIC_YEAR] = Config::academic_year;
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