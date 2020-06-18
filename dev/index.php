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
		<div class="card text-center border-primary mx-auto my-5" style="width: 30rem">
			<div class="card-body">
			<h3 class="card-title">Database tools:</h3>

			<div class="card-text"><a href="arbor_import.php" class="btn btn-success" role="button">Refresh from Arbor.</a></div>

			<div class="card-text"><a href="manage_subjects.php" class="btn btn-primary" role="button">Manage subjects and groups.</a></div>

			<div class="card-text"><a href="manage_tests.php" class="btn btn-primary" role="button">Manage tests and grade boundaries.</a></div>

			<?php if (isset($_GET['showmethegoodstuff']) && $_GET['showmethegoodstuff'] === "yes") { ?>
				<div class="card-text"><a href="database_setup.php" class="btn btn-danger" role="button">Initial database setup- DELETES ALL DATA!</a></div>
			<?php } else { ?>
				<div class="card-text"><a href="?showmethegoodstuff=yes" class="btn btn-warning" role="button">Show me the really dangerous stuff.</a></div>
			<?php } ?>
			</div>
		</div>
	</body>
</html>