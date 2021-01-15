<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (isset($_FILES["input-file"])) {
    $f = $_FILES["input-file"];
    $pages = [];
    if ($f['size'] > 0 && substr($f['name'], -5, 5) == ".docx" && strlen($f['name'] < 35)) {
        (new FeedbackSheet([
            FeedbackSheet::NAME => str_replace('.docx', '', $f['name']),
            FeedbackSheet::TEMPLATEDATA => addslashes(file_get_contents($f['tmp_name'])),
        ]))->commit();
    } else {
        die("Must be a .docx file please, with name of under 30 characters.");
    }
}
 
foreach (FeedbackSheet::retrieveAll(FeedbackSheet::ID) as $template) {
    if (isset($_GET['delete']) && $_GET['delete'] == $template->getId()) {
        // Check it's not used by any subject
        $subjects = Subject::retrieveByDetail(Subject::FEEDBACKSHEET_ID, $template->getId());
        if (count($subjects) > 0) {
            echo "<div><a href=\"manage_feedback_sheets.php\">&lt Back</a></div>";
            echo "<div>Sorry, {$template->getName()} is in use by these subjects:</div><div><ul>";
            foreach ($subjects as $s) {
                echo "<li>{$s->getName()}</li>";
            }
            die ('</ul></div><div><a href="manage_subjects.php">Please remove them before deleting them here.</a></div>');
        } else {
            FeedbackSheet::delete($_GET['delete']);
        }
    } else if (isset($_GET['download']) && $_GET['download'] == $template->getId()) {
        header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("content-disposition: attachment;filename=\"{$template->getName()}.docx\"");

        echo $template->get(FeedbackSheet::TEMPLATEDATA);
        die();
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
<form method="post" enctype="multipart/form-data">
<table class="table table-hover table-sm">
<thead><tr><th>Feedback sheet</th><th>Action</th></tr></thead>
<?php

foreach (FeedbackSheet::retrieveAll(FeedbackSheet::NAME) as $template) {
    echo "<tr>";
    echo "<td><a href=\"?download={$template->getId()}\">{$template->getName()}</a></td>";
    echo "<td><a href=\"?delete={$template->getId()}\" class=\"btn btn-danger\">Delete</a></td>";
    echo "</tr>";
}

echo "<tr>";
echo <<<EOF
<td>
    <div class="form-group">
        <label class="form-label" for="input-file">New template (.docx only, filename under 30 chars)</label>
        <input type="file" class="form-control-file" name="input-file" id="input-file">
    </div>
</td>
<td>
    <input type="submit" class="form-control btn btn-warning" value="Upload new feedback sheet">
</td>
EOF;
echo "</tr>";
?>
</table>
</form>
</div>
</body>
</html>