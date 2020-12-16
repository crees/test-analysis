<nav class="navbar navbar-expand">
            <!-- Brand -->
    <a class="navbar-brand"><?= $auth_user ?></a>
    
    <!-- Toggler/collapsibe Button -->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
    	<span class="navbar-toggler-icon">collapse</span>
    </button>
    
    <!-- Navbar links -->
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
    	<ul class="navbar-nav">
    		<li class="nav-item">
        		<a class="nav-link" href="<?= TestAnalysis\Config::site_url; ?>/index.php?session_destroy=<?= $_SESSION['SESSION_CREATIONTIME']; ?>">Home</a>
        	</li>
<!--
    		<li class="nav-item">
        		<a class="nav-link" href="<?= TestAnalysis\Config::site_url; ?>/topic_overview.php">Topic overview</a>
        	</li>

    		<li class="nav-item">
        		<a class="nav-link" href="<?= TestAnalysis\Config::site_url; ?>/skillset_overview.php">Skillset overview</a>
        	</li>
-->        	
        	<li class="nav-item dropdown">
        		<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          			Online papers
        		</a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="<?= TestAnalysis\Config::site_url; ?>/test_upload.php">Upload tests</a>
                    <a class="dropdown-item" href="<?= TestAnalysis\Config::site_url; ?>/test_mark.php">Mark tests</a>
                    <a class="dropdown-item" href="<?= TestAnalysis\Config::site_url; ?>/test_scanned_scores.php">Review test scores</a>
                </div>
              </li>
    	</ul>
	</div>
	<span class="navbar-text">
		<a class="nav-link" href="dev">Manage database</a>
	</span>
</nav>

<div class="h3 text-center"><img src="img/dshs.jpg" style="width: 30%;" /></div>
<div class="h3 text-center"><?= \TestAnalysis\Config::site ?></div>
