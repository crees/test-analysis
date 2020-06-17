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
			<div class="h1">Database tools:</div>

			<div class="row"><a href="arbor_import.php" class="btn btn-primary" role="button">Refresh from Arbor.</a></div>

			<div class="row"><a href="manage_subjects.php" class="btn btn-primary" role="button">Manage subjects and groups.</a></div>

			<?php if (isset($_GET['showmethegoodstuff']) && $_GET['showmethegoodstuff'] === "yes") { ?>
				<div class="row"><a href="database_setup.php" class="btn btn-danger" role="button">Initial database setup- DELETES ALL DATA!</a></div>
			<?php } else { ?>
				<div class="row"><a href="?showmethegoodstuff=yes" class="btn btn-danger" role="button">Show me the really dangerous stuff.</a></div>
			<?php } ?>
		</div>
	</body>
</html>