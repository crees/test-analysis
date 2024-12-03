<?php 

namespace TestAnalysis;

// Deal with persistent settings such as subject, group and test
$getVars = [];
foreach (['subject', 'teaching_group', 'test'] as $persistentVar) {
    if (isset($_GET[$persistentVar])) {
        array_push($getVars, "$persistentVar={$_GET[$persistentVar]}");
    }
}
if (!empty($getVars)) {
    $getVars = '?' . implode('&', $getVars);
} else {
    $getVars = '?';
}
?>

<nav class="navbar navbar-expand">
            <!-- Brand -->
    <a class="navbar-brand"><?php 
    $staff = $staff ?? Staff::me($auth_user);
    echo $staff->getName();
    if ($staff->adminType() == Staff::ADMIN_TYPE_GLOBAL) {
        echo "<span class=\"text-danger\"> (admin)</span>";
    } elseif ($staff->adminType() == Staff::ADMIN_TYPE_DEPARTMENT) {
        echo "<span class=\"text-warning\"> (department admin)</span>";
    }
    ?></a>
    
    <!-- Toggler/collapsibe Button -->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    	<span class="navbar-toggler-icon">collapse</span>
    </button>
    
    <!-- Navbar links -->
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
    	<ul class="navbar-nav">
    		<li class="nav-item">
        		<a class="nav-link" href="<?= Config::site_url; ?>/index.php?session_destroy=<?= $_SESSION['SESSION_CREATIONTIME'] ?? '' ?>">Home</a>
        	</li>
    		<li class="nav-item">
			<a class="nav-link" href="<?= Config::site_url; ?>/overview.php<?= $getVars ?>">Overview</a>
        	</li>
<!--
    		<li class="nav-item">
        		<a class="nav-link" href="<?= Config::site_url; ?>/topic_overview.php">Topic overview</a>
        	</li>

    		<li class="nav-item">
        		<a class="nav-link" href="<?= Config::site_url; ?>/skillset_overview.php">Skillset overview</a>
        	</li>
-->        	
        	<li class="nav-item dropdown">
        		<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          			Online papers
        		</a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_upload.php<?= $getVars ?>">Upload tests to complete online</a>
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_completed_upload.php<?= $getVars ?>">Upload tests already completed on paper</a>
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_mark.php<?= $getVars ?>">Mark tests</a>
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_scanned_scores.php<?= $getVars ?>">Review test scores</a>
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_bulk_download.php<?= $getVars ?>">Download a class's tests for printing</a>
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_worklist.php">My worklist</a>
                </div>
            </li>

        	<li class="nav-item dropdown">
        		<a class="nav-link dropdown-toggle" href="#" id="prefsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          			Preferences
        		</a>
        		<div class="dropdown-menu">
                	<a class="dropdown-item" href="?theme=default">Default theme</a>
                	<a class="dropdown-item" href="?theme=darkly">Dark theme</a>
                	<div class="dropdown-divider"></div>
                	<?php if ($staff->get(Staff::LARGE_MARKING) == 0) { ?>
                		<a class="dropdown-item" href="?large_marking=1">Make the tests fill screen width</a>
                	<?php } else { ?>
                		<a class="dropdown-item" href="?large_marking=0">Make the tests fill screen height</a>
                	<?php } ?>
                	<?php if ($staff->get(Staff::MARK_BY_STUDENT) == 0) {
                		echo "<a class=\"dropdown-item\" href=\"$getVars&mark_by_student=1\">Mark by student</a>";
                	} else {
                		echo "<a class=\"dropdown-item\" href=\"$getVars&mark_by_student=0\">Mark by question/page</a>";
                	} ?>
                	<?php if ($staff->get(Staff::DEFAULT_MARKING_TOOL) === 'pen') { ?>
                		<a class="dropdown-item" href="?default_marking_tool=tick">Set the default marking tool to tick</a>
                	<?php } else { ?>
                		<a class="dropdown-item" href="?default_marking_tool=pen">Set the default marking tool to pen</a>
                	<?php } ?>
                	<div class="dropdown-divider"></div>
                	<a class="dropdown-item" href="change_name.php">Change my display name</a>
                </div>
        	</li>
	
            
    	</ul>
	</div>
	
	<?php if ($staff->isDepartmentAdmin()) { ?>
	<span class="navbar-text">
		<a class="nav-link" href="dev">Manage database</a>
	</span>
	<?php } ?>
</nav>

<div class="h3 text-center"><img src="img/<?= Config::site_small_logo ?>" style="width: 30%;" /></div>
<div class="h3 text-center"><?php echo Config::site; if (isset($pageTitle)) echo ": $pageTitle"; ?></div>
