<?php
namespace TestAnalysis;

include "../bin/classes.php";

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
            if (is_null($baseline['grade']) || is_null($baseline['grade']['displayName'])) {
                continue;
            }
            $details[Baseline::GRADE] = $baseline['grade']['displayName'];
            $details[Baseline::STUDENT_ID] = $baseline['student']['id'];
            $details[Baseline::NAME] = $baseline['assessment']['displayName'];
            $details[Baseline::MIS_ASSESSMENT_ID] = $baseline['assessment']['id'];
            $dBaseline = new Baseline($details);
            $dBaseline->commit();
        }
        $page_num += 1;
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <title>Import from Arbor</title>
    </head>
    <body>
    <h1>Importing from Arbor...</h1>
    <div id="status">Baselines done!  Please wait while we work on Page 0...</div>
    <script>
	function requested() {
		page = this.response.match(/\d+/)[0];
		statusparagraph = document.getElementById('status');
		if (page == 0) {
			statusparagraph.innerHTML = 'Complete!  <a href="<?= Config::site_url ?>/dev">Back to database.';
		} else {
			statusparagraph.innerHTML = "Working on page " + page + "...";
			doPage(page);
		}			
	}

	function doPage(page) {
		var xhr = new XMLHttpRequest();
	    xhr.addEventListener("load", requested);
	    xhr.open("GET", 'arbor_import.php?baseline_done=yes&year_page=' + page);
		xhr.send();
	}

	doPage(0);

    </script>
    </body>
    </html>
    
    <?php
    die();
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
    dependantUnits {
      id
    }
    allMemberships {
      startDate
      endDate
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
    // That's it, done.    
    die('0');
}

// Clear old memberships out
if ($year_page == 0) {
    (new Database())->dosql("DELETE FROM studentgroupmembership;");
}

$year_page++;

$allStudents = Student::retrieveAll();

foreach ($data['AcademicUnit'] as $group) {
    if (count($group['dependantUnits']) > 0) {
        continue;
    }
    $displayNames = explode(":", $group['displayName']);
    $displayName = end($displayNames);
    if (!empty($dGroup = TeachingGroup::retrieveByDetail(TeachingGroup::ID, $group['id']))) {
        $dGroup = $dGroup[0];
        $dGroup->setName(trim($displayName));
        //echo "<div class=\"row\">Scanned TeachingGroup: " . $dGroup->getName() . "</div>"; 
    } else {
        $group[TeachingGroup::NAME] = trim($displayName);
        $group[TeachingGroup::ACADEMIC_YEAR] = Config::academic_year;
        $group[TeachingGroup::ID] = 
        $dGroup = new TeachingGroup($group);
        //echo "<div class=\"row\">New TeachingGroup: " . $dGroup->getName() . "</div>";
    }
    $dGroup->commit();

    foreach ($group['allMemberships'] as $membership) {
        if (strtotime($membership['startDate']) > time() || strtotime($membership['endDate']) < time()) {
            continue;
        }
        $dStudent = null;
        foreach ($allStudents as $student) {
            if ($student->getId() == $membership['student']['id']) {
                $dStudent = $student;
                $dStudent->setNames($membership['student']['firstName'], $membership['student']['lastName']);
                break;
            }
        }
        if (is_null($dStudent)) {
            $dStudent = new Student([
                Student::ID         => $membership['student']['id'],
                Student::FIRST_NAME => $membership['student']['firstName'],
                Student::LAST_NAME  => $membership['student']['lastName'],
            ]);
            //echo "<div class=\"row\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;New Student: " . $dStudent->getName() . "</div>";
            array_push($allStudents, $dStudent);
        }
        $dGroup->addMember($dStudent);
        
        $dStudent->commit();
        
        // echo "... added to db and membership made</div>";
    }
    
    //echo "<div class=\"row\">Group {$dGroup->getName()} now has $numGroupMembers members.</div>";
}

//header("Location: arbor_import.php?baseline_done=yes&year_page=$year_page");
//echo "<div class=\"row\"><a href=\"?baseline_done=yes&year_page=$year_page\" class=\"btn btn-primary\">Now click for Page $year_page</a></div>";
echo "$year_page";
