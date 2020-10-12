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
            if (is_null($details[Baseline::GRADE])) {
                continue;
            }
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

if (empty($_GET['year_page'])) {
    $year_page = 0;
} else {
    $year_page = $_GET['year_page'];
}

$query = "query {
  AcademicUnit (academicYear__code: \"" . Config::academic_year . "\" page_size: 100 page_num: $year_page) {
    id
    displayName
    allMemberships {
      student {
        id
        lastName
        firstName
      }
    }
  }
}";

$data = $client->rawQuery($query)->getData();

if (empty($data['AcademicUnit'])) {
    die("<div class=\"row\">Complete!</div>");
}

// Clear old memberships out
if ($year_page == 0) {
    (new Database())->dosql("DELETE FROM studentgroupmembership;");
}

$year_page++;
echo "<div class=\"row\"><a href=\"?baseline_done=yes&year_page=$year_page\" class=\"btn btn-primary\">Now click for Page $year_page</a></div>";

foreach ($data['AcademicUnit'] as $group) {
    $displayNames = explode(":", $group['displayName']);
    $displayName = end($displayNames);
    if (!empty($dGroup = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $group['id']))) {
        $dGroup = $dGroup[0];
        $dGroup->setName($displayName);
        echo "<div class=\"row\">Scanned TeachingGroup: " . $dGroup->getName() . "</div>"; 
    } else {
        $group[TeachingGroup::NAME] = $displayName;
        $group[TeachingGroup::ACADEMIC_YEAR] = Config::academic_year;
        $dGroup = new TeachingGroup($group);
        echo "<div class=\"row\">New TeachingGroup: " . $dGroup->getName() . "</div>";
    }
    $dGroup->commit();
    $numGroupMembers = 0;
    foreach ($group['allMemberships'] as $membership) {
        if (!empty($dStudent = Student::retrieveByDetail(Student::ID, $membership['student']['id']))) {
            $dStudent = $dStudent[0];
            $dStudent->setNames($membership['student']['firstName'], $membership['student']['lastName']);
            // echo "<div class=\"row\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Scanned Student: " . $dStudent->getName();
        } else {
            $dStudent = new Student([
                Student::ID         => $membership['student']['id'],
                Student::FIRST_NAME => $membership['student']['firstName'],
                Student::LAST_NAME  => $membership['student']['lastName'],
            ]);
            echo "<div class=\"row\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;New Student: " . $dStudent->getName() . "</div>";
        }
        $dGroup->addMember($dStudent);
        
        $dStudent->commit();
        
        $numGroupMembers++;
        
        // echo "... added to db and membership made</div>";
    }
    
    echo "<div class=\"row\">Group {$dGroup->getName()} now has $numGroupMembers members.</div>";
}
?>
	</div>
</body>
</html>