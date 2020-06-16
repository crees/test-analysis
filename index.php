<?php
namespace TestAnalysis;

require "bin/classes.php";

$allSubjects = Subject::retrieveAll(Subject::NAME);

if (isset($_GET['subject']) && !empty($_GET['subject'])) {
    $subject = Subject::retrieveByDetail(Subject::ID, $_GET['subject'])[0];
    $teachingGroups = $subject->getTeachingGroups();
    $tests = Test::retrieveByDetail(Test::SUBJECT_ID, $_GET['subject']);
    
    if (isset($_GET['teaching_group']) && !empty($_GET['teaching_group'])) {
        $view = new View($tests, TeachingGroup::retrieveByDetail(TeachingGroup::ID, $_GET['teaching_group'])[0]->getStudents());
    } else {
        $view = new View($tests, $subject->getStudents());
    }
}

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
		
		<form method="GET">
    		<div class="form-group row">
    			<label for="subject" class="col-2 col-form-label">Subject</label>
      			<div class="col-10">
        			<select class="form-control" id="subject" name="subject" onchange="this.form.submit()">
        				<?php
        				if (!isset($_GET['subject'])) {
        				    echo "<option value=\"\" selected>Please select subject</option>";
        				}
        				foreach ($allSubjects as $s) {
        				    if (isset($_GET['subject']) && $_GET['subject'] === $s->getId()) {
        				        $selected = "selected";
        				    } else {
        				        $selected = "";
        				    }
        				    echo "<option value=\"" . $s->getId() . "\" $selected>" . $s->getName() . "</option>";
        				}
        				?>
        			</select>
        		</div>
        		<?php if (isset($_GET['subject'])) { ?>
            		<label for="teaching_group" class="col-2 col-form-label">Teaching group</label>
            		<div class="col-10">
            			<select class="form-control" id="teaching_group" name="teaching_group" onchange="this.form.submit()">
            				<option value="">All groups</option>
            				<?php
            				foreach ($teachingGroups as $g) {
            				    if (isset($_GET['teaching_group']) && $_GET['teaching_group'] === $g->getId()) {
            				        $selected = "selected";
            				    } else {
            				        $selected = "";
            				    }
            				    echo "<option value=\"" . $g->getId() . "\" $selected>" . $g->getName() . "</option>";
            				}
            				?>
            			</select>
          			</div>
          		<?php } /* isset($_GET['subject']) */ ?>
    		</div>
		</form>
		<?php 
		if (isset($view)) {
		    $view->print();
		}
		?>
	</div>
</body>

</html>