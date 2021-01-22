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
    $getVars = '';
}
?>

<nav class="navbar navbar-expand">
            <!-- Brand -->
    <a class="navbar-brand"><?php 
    echo $_SESSION['staff']->getName();
    if ($_SESSION['staff']->adminType() == Staff::ADMIN_TYPE_GLOBAL) {
        echo "<span class=\"text-danger\"> (admin)</span>";
    } elseif ($_SESSION['staff']->adminType() == Staff::ADMIN_TYPE_DEPARTMENT) {
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
        		<a class="nav-link" href="<?= Config::site_url; ?>/index.php?session_destroy=<?= $_SESSION['SESSION_CREATIONTIME'] ?>">Home</a>
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
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_upload.php<?= $getVars ?>">Upload tests</a>
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_mark.php<?= $getVars ?>">Mark tests</a>
                    <a class="dropdown-item" href="<?= Config::site_url ?>/test_scanned_scores.php<?= $getVars ?>">Review test scores</a>
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
                </div>
        	</li>
	
            
    	</ul>
	</div>
	
	<?php if ($_SESSION['staff']->isDepartmentAdmin()) { ?>
	<span class="navbar-text">
		<a class="nav-link" href="dev">Manage database</a>
	</span>
	<?php } ?>
</nav>

<div class="h3 text-center"><img src="img/<?= Config::site_small_logo ?>" style="width: 30%;" /></div>
<div class="h3 text-center"><?php echo Config::site; if (isset($pageTitle)) echo ": $pageTitle"; ?></div>
