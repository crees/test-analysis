<?php
namespace TestAnalysis;

require "bin/classes.php";
?>
<!doctype html>
<html>

<head>
<?php require "bin/head.php"; ?>
</head>

<body>
	<div class="container">
        <nav class="navbar navbar-expand">
            <!-- Brand -->
            <a class="navbar-brand">Actions</a>
            
            <!-- Toggler/collapsibe Button -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
            	<span class="navbar-toggler-icon">collapse</span>
            </button>
            
            <!-- Navbar links -->
            <div class="collapse navbar-collapse" id="collapsibleNavbar">
            	<ul class="navbar-nav">
            		<li class="nav-item">
                		<a class="nav-link" href="?session_destroy=<?= $_SESSION['SESSION_CREATIONTIME']; ?>">Destroy session</a>
                	</li>
            	</ul>
        	</div>
        </nav>

		<h3 class="mb-4"><?= Config::site ?></h3>
	</div>
</body>

</html>