<?php
namespace TestAnalysis;

include "../bin/classes.php";

// OK, so here we need to check the right people are in.
$departments = Department::staffAdminDepartments($_SESSION['staff']);

if (empty($departments)) {
    die("Hm, you should not be here!"); 
}

if (isset($_GET['removeStaff'])) {
    $deptId = $_GET['applyToDepartment'];
    if (!isset($departments[$deptId])) {
        die("You should not be working on someone else's department!");
    }
    $departments[$deptId]->removeStaff(Staff::retrieveByDetail(Staff::ID, $_GET['removeStaff'])[0]);
    $url = explode("?", "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")[0];
    header("Location: $url");
    die();
} else if (isset($_GET['toggleAdmin'])) {
    $deptId = $_GET['applyToDepartment'];
    if (!isset($departments[$deptId])) {
        die("You should not be working on someone else's department!");
    }
    if ($_GET['toggleAdmin'] == $_SESSION['staff']->getId() && !$_SESSION['staff']->isGlobalAdmin()) {
        echo "You can't demote yourself-- get someone else to do it if you need to!";
    } else {
        $m = StaffDepartmentMembership::retrieveByDetails([StaffDepartmentMembership::DEPARTMENT_ID, StaffDepartmentMembership::STAFF_ID], [$_GET['applyToDepartment'], $_GET['toggleAdmin']])[0];
        $m->toggleAdmin();
        $url = explode("?", "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")[0];
        header("Location: $url");
        die();
    }
} else {
    foreach ($_POST as $k => $value) {
        if (!empty($value)) {
            if (str_contains($k, "department-add-staff-")) {
                $deptId = str_replace("department-add-staff-", "", $k);
                if (!isset($departments[$deptId])) {
                    die("You should not be working on someone else's department!");
                }
                $s = Staff::retrieveByDetail(Staff::ID, $value)[0];
                $departments[$deptId]->addStaff($s);
            }
        }
        if (str_contains($k, "global-admin-for-")) {
            $staffId = str_replace("global-admin-for-", "", $k);
            if ($value == "checked") {
                if (!isset($_POST["global-admin-$staffId"])) {
                    if ($staffId == $_SESSION['staff']->getId()) {
                        echo "You can't demote yourself-- get someone else to do it if you need to!";
                    } else {
                        $s = Staff::retrieveByDetail(Staff::ID, $staffId)[0];
                        $s->setGlobalAdmin(0);
                        $s->commit();
                    }
                }
            } else {
                if (isset($_POST["global-admin-$staffId"])) {
                    $s = Staff::retrieveByDetail(Staff::ID, $staffId)[0];
                    $s->setGlobalAdmin(1);
                    $s->commit();
                }
            }
        }
    }
}
?>
<!doctype html>
<html><head><?php require "../bin/head.php" ?></head>
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
<form method="post">
<table class="table table-sm table-hover">
<thead><tr><th>Department</th><th>Department admins (click to demote)</th><th>Staff (click to remove), <i class="fas fa-crown"> to promote</i></th><th>Add staff</th></tr></thead>
<?php
foreach ($departments as $dept) {
    $allStaff = Staff::retrieveAll(Staff::LAST_NAME);
    echo "<tr>";
    echo "<td>{$dept->getName()}</td>";
    $admins = [];
    $names = [];
    foreach ($dept->getAdmins() as $s) {
        array_push($admins, "<a href=\"?toggleAdmin=" . $s->getId() . "&applyToDepartment=" . $dept->getId() . "\">" . $s->getName() . "</a>");
        unset($allStaff[array_search($s, $allStaff)]);
    }
    echo "<td>" . implode(", ", $admins) . "</td>";
    foreach ($dept->getUsers() as $s) {
        array_push($names, "<a href=\"?toggleAdmin={$s->getId()}&applyToDepartment={$dept->getId()}\"><i class=\"fas fa-crown\"></i></a><a href=\"?removeStaff=" . $s->getId() . "&applyToDepartment=" . $dept->getId() . "\">" . $s->getName() . "</a>");
        unset($allStaff[array_search($s, $allStaff)]);
    }
    echo "<td>" . implode(", ", $names) . "</td>";
    
    echo "<td><select name=\"department-add-staff-" . $dept->getId() . "\" onchange=\"this.form.submit()\">";
    echo "<option value=\"\" selected>Add staff to " . $dept->getName() . "</option>";
    foreach ($allStaff as $s) {
        echo "<option value=\"" . $s->getId() . "\">" . $s->getName() . "</option>";
    }
    echo "</select></td>";
    echo "</tr>";
}
?>
</table>

<?php

if ($_SESSION['staff']->isGlobalAdmin()) {
    echo "<h4>Global admins</h4>";
    $staff = [];
    $globalAdmins = [];
    foreach (Staff::retrieveAll(Staff::LAST_NAME) as $s) {
        if ($s->isGlobalAdmin()) {
            array_push($globalAdmins, $s);
        } else {
            array_push($staff, $s);
        }
    }
    $staff = array_merge($globalAdmins, $staff);
    foreach ($staff as $s) {
        $checked = $s->isGlobalAdmin() ? 'checked' : '';
        echo <<< eof
<div class="row">
    <div class="form-check">
        <input type="hidden" name="global-admin-for-{$s->getId()}" value="$checked">
        <input type="checkbox" class="form-check-input" id="global-admin-{$s->getId()}" name="global-admin-{$s->getId()}" $checked>
        <label class="form-check-label" for="global-admin-{$s->getId()}">{$s->getName()}</label>
    </div>
</div>
eof;
    }
    echo "<input class=\"btn btn-primary\" type=\"submit\" value=\"Submit\">";
}
?>
</form>
</div>
</body>
</html>