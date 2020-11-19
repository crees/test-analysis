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

    		<li class="nav-item">
        		<a class="nav-link" href="topic_overview.php">Topic overview</a>
        	</li>

    		<li class="nav-item">
        		<a class="nav-link" href="skillset_overview.php">Skillset overview</a>
        	</li>
    	</ul>
	</div>
	<span class="navbar-text">
		<a class="nav-link" href="dev">Manage database</a>
	</span>
</nav>

<div class="h3"><img src="img/dshs.jpg" style="width: 30%;" /><?= \TestAnalysis\Config::site ?></div>
