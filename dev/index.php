<?php 

namespace TestAnalysis;

require "../bin/classes.php";

?>
<!doctype html>
<html>
	<head>
		<?php require "../bin/head.php"; ?>
	</head>
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
                	</ul>
            	</div>
            </nav>
    		<div class="card text-center border-primary mx-auto my-5" style="width: 30rem">
    			<div class="card-body">
    			<h3 class="card-title">Database tools:</h3>
    
    			<div class="card-text"><a href="arbor_import.php" class="btn btn-success" role="button">Refresh from Arbor (might take ten minutes or so, be patient!)</a></div>
    
    			<div class="card-text"><a href="manage_departments.php" class="btn btn-primary" role="button">Manage departments</a></div>
    
    			<div class="card-text"><a href="manage_subjects.php" class="btn btn-primary" role="button">Manage subjects and groups</a></div>
    
    			<div class="card-text"><a href="manage_topics.php" class="btn btn-primary" role="button">Manage topics</a></div>
    
    			<div class="card-text"><a href="manage_tests.php" class="btn btn-primary" role="button">Manage tests, targets and grade boundaries</a></div>
    			
    			<div class="card-text"><a href="manage_test_subject_membership.php" class="btn btn-primary" role="button">Assign tests to subjects</a></div>
    
    			<?php if (isset($_GET['showmethegoodstuff']) && $_GET['showmethegoodstuff'] === "yes") { ?>
    				<div class="card-text"><a href="database_setup.php" class="btn btn-danger" role="button">Initial database setup- DELETES ALL DATA!</a></div>
    			<?php } else { ?>
    				<div class="card-text"><a href="?showmethegoodstuff=yes" class="btn btn-warning" role="button">Show me the really dangerous stuff.</a></div>
    			<?php } ?>
    			</div>
    		</div>
    	</div>
	</body>
</html>