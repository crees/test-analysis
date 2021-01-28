<?php
namespace TestAnalysis;

$pageTitle = "Change display name";

require "bin/classes.php";
require "dev/upgrade_database.php";

$staff = Staff::me($auth_user);

if (isset($_GET['firstName']) && isset($_GET['lastName'])) {
    $staff->setNames($_GET['firstName'], $_GET['lastName']);
    $staff->commit();
}

?>
<!doctype html>
<html>

<head>
<?php require "bin/head.php"; ?>
</head>

<body>
	<div class="container">
		<?php require "bin/navbar.php"; ?>
		
		<div class="card text-center border-primary mx-auto my-5" style="width: 18rem;">
			<div class="card-body">
				<h5 class="card-title">Change display name</h5>
				
    			<p class="card-text">
    				This is displayed only in the staff area next to recorded marks-- students will not see this.
    			</p>
    
        		<form method="get">
        			<div class="form-group">
        			    <label for="firstName">First name</label>
        			    
            			<input type="text" class="form-control" id="firstName" name="firstName" value="<?= $staff->get(Staff::FIRST_NAME); ?>">
        			</div>
        			
        			<div class="form-group">
        				<label for="lastName">Last name</label>
        				
        				<input type="text" class="form-control" id="lastName" name="lastName" value="<?= $staff->get(Staff::LAST_NAME); ?>">
        			</div>
        			
                    <button type="submit" class="btn btn-primary">Save</button>
        		</form>
    		</div>
		</div>
	</div>
</body>
</html>