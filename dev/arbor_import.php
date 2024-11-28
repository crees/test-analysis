<?php
namespace TestAnalysis;

$backup_key_override_auth = true;

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
    var kidsDone = 0;
	function requested() {
		var page = this.response.match(/^\d+$/)[0];
		statusparagraph = document.getElementById('status');
		if (page == 0) {
			if (kidsDone == 0) {
				kidsDone = 1;
			} else {
				statusparagraph.innerHTML = 'Complete!  <a href="<?= Config::site_url ?>/dev">Back to database.';
				return true;
			}
		}
		var status = kidsDone == 0 ? 'Working on importing students, ' : 'Now grouping students, ';
		statusparagraph.innerHTML = status + "page " + page + "...";
		doPage(page);
	}

	function doPage(page) {
		var xhr = new XMLHttpRequest();
	    xhr.addEventListener("load", requested);
	    var queryString = 'arbor_import.php?baseline_done=yes&year_page=' + page;
	    if (kidsDone == 1)
		    queryString = queryString + '&kidsDone=yes';
	    xhr.open("GET", queryString);
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

// Let's get Students first

if (empty($_GET['kidsDone'])) {
    $query = "query { 
      Student (currentlyEnrolled: true page_size: 1000 page_num: $year_page) {
        id
        firstName
        lastName
        gender {
          code
        }
        activeSenNeeds {
          id
          displayName
          description
        }
        senStatus {
          id
          displayName
          code
        }
      	nativeLanguages {
          id
          dataOrder
          displayName
          __typename
        }
        inCareStatusAssignments {
          id
          displayName
          inCareStatus {
            active
          }
        }
        pupilPremiumRecipients {
          id
          displayName
          endDate
        }
      }
    }";
    
    $data = $client->rawQuery($query)->getData();
    
    if (empty($data['Student'])) {
        die('0');
    }
    
    $allStudents = Student::retrieveAll();
    
    foreach ($data['Student'] as $student) {
        $current_student = null;
        $gender = '?';
        if (isset($student['gender']['code'])) {
            $gender = $student['gender']['code'][0];
        }
        foreach ($allStudents as $s) {
            if ($s->getId() == $student['id']) {
                $current_student = $s;
            }
        }
        if (is_null($current_student)) {
            // Create new Student
            $current_student = new Student([
                Student::ID     =>      $student['id'],
                Student::FIRST_NAME =>  $student['firstName'],
                Student::LAST_NAME =>   $student['lastName'],
                Student::GENDER =>      $gender,
            ]);
        } else {
            $current_student->setNames($student['firstName'], $student['lastName']);
            $current_student->setGender($gender);
        }
        $current_student->commit();
        // Now get Tagging
        $olddemographics = Demographic::retrieveByDetail(Demographic::STUDENT_ID, $current_student->getId());
        $newdemographics = [];
        $tag = function($tagName, $mis_id, $value = null) use (&$olddemographics, &$newdemographics, $current_student) {
            $exists = false;
            foreach ($olddemographics as $index => $d) {
                if ($d->get(Demographic::MIS_ID) == $mis_id && $d->get(Demographic::TAG) == $tagName) {
                    $newdemographics[] = new Demographic([
                        Demographic::ID => $d->getId(),
                        Demographic::TAG => $tagName,
                        Demographic::MIS_ID => $d->get(Demographic::MIS_ID),
                        Demographic::STUDENT_ID => $d->get(Demographic::STUDENT_ID),
                        Demographic::DETAIL => $value,
                    ]);
                    unset($olddemographics[$index]);
                    $exists = true;
                    break;
                }
            }
            if ($exists == false) {
                $newdemographics[] = new Demographic([
                    Demographic::MIS_ID => $mis_id,
                    Demographic::STUDENT_ID => $current_student->getId(),
                    Demographic::TAG => $tagName,
                    Demographic::DETAIL => $value
                ]);
            }
        };
        
        foreach ($student['activeSenNeeds'] as $need) {
            $tag(Demographic::TAG_SEN_NEED, $need['id'], "{$need['displayName']}: {$need['description']}");
        }
        
        if (!is_null($student['senStatus']) && $student['senStatus']['code'] != Config::no_sen_code) {
            $tag(Demographic::TAG_SEN_STATUS, $student['senStatus']['id'], "{$student['senStatus']['displayName']}");
        }
        
        if (isset($student['nativeLanguages'][1]) || 
            isset($student['nativeLanguages'][0]) &&
                $student['nativeLanguages'][0]['displayName'] != Config::our_native_languge) {
            foreach ($student['nativeLanguages'] as $lang) {
                $tag(Demographic::TAG_NATIVE_LANGUAGES, $lang['id'], $lang['displayName']);
            }
        }
        
        if (isset($student['inCareStatusAssignments'][0])) {
            foreach ($student['inCareStatusAssignments'] as $care) {
                if ($care['inCareStatus']['active'] == true) {
                    $tag(Demographic::TAG_IN_CARE_STATUS, $care['id'], $care['displayName']);
                }
            }
        }
        
        if (isset($student['pupilPremiumRecipients'][0])) {
            foreach ($student['pupilPremiumRecipients'] as $ppi) {
                if (strtotime($ppi['endDate']) > time()) {
                    $tag(Demographic::TAG_PUPIL_PREMIUM, $ppi['id'], $ppi['displayName']);
                }
            }
        }
        
        foreach ($olddemographics as $d) {
            Demographic::delete($d->getId());
        }
        
        foreach ($newdemographics as $d) {
            $d->commit();
        }
    }
    
    $year_page++;
    die("$year_page");
}

$query = "query {
  AcademicUnit (academicYear__code: \"" . get_current_AY() . "\" page_size: 100 page_num: $year_page) {
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
      }
    }
  }
}";

$data = $client->rawQuery($query)->getData();

if (empty($data['AcademicUnit'])) {
    // Now we need to clear out any memberships that haven't been touched in five minutes.
    StudentGroupMembership::trimBefore(3000);
    die('0');
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
        $group[TeachingGroup::ACADEMIC_YEAR] = get_current_AY();
        $group[TeachingGroup::ID] = $group['id'];
        $dGroup = new TeachingGroup($group);
        //echo "<div class=\"row\">New TeachingGroup: " . $dGroup->getName() . "</div>";
    }
    $dGroup->commit();

    foreach ($group['allMemberships'] as $membership) {
        if (strtotime($membership['startDate']) > time() || strtotime($membership['endDate']) < time()) {
            continue;
        }
        StudentGroupMembership::update_or_create([
            StudentGroupMembership::STUDENT_ID => $membership['student']['id'],
            StudentGroupMembership::TEACHINGGROUP_ID => $dGroup->getId(),
        ], []);

        // echo "... added to db and membership made</div>";
    }
    
    //echo "<div class=\"row\">Group {$dGroup->getName()} now has $numGroupMembers members.</div>";
}

echo "$year_page";
