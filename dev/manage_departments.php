<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (!empty($_POST['dept-name-new'])) {
    (new Department([
        Department::NAME => $_POST['dept-name-new'],
    ]))->commit();
}
 
foreach (Department::retrieveAll(Department::ID) as $dept) {
    if (isset($_POST["dept-name-{$dept->getId()}"]) && 
            ($_POST["dept-name-{$dept->getId()}"] != $dept->getName())
            ) {
        (new Department([
            Department::ID => $dept->getId(),
            Department::NAME => $_POST["dept-name-{$dept->getId()}"],
        ]))->commit();
    }
}
?>
<!doctype html>
<html><head><?php require "../bin/head.php" ?></head>
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
            	<li class="nav-item">
            		<a class="nav-link" href="index.php">Database management</a>
            	</li>
        	</ul>
    	</div>
    </nav>
<form method="post">
<table class="table table-hover table-sm">
<thead><tr><th>Department</th></tr></thead>
<?php

foreach (Department::retrieveAll(Department::NAME) as $dept) {
    echo "<tr>";
    echo View::makeTextBoxCell("dept-name-" . $dept->getId(), $dept->getName());
    echo "</tr>";
}

echo "<tr>";
echo View::makeTextBoxCell("dept-name-new", "");
echo "</tr>";
?>
<tr><td colspan="2"><input class="form-control" type="submit" value="Save"></td></tr>
</table>
</form>
</div>
</body>
</html>