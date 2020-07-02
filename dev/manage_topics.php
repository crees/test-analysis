<?php
namespace TestAnalysis;

include "../bin/classes.php";

if (!empty($_POST['topic-name-new'])) {
    (new TestTopic([
        TestTopic::NAME => $_POST['topic-name-new'],
        TestTopic::SUBJECT_ID => $_POST['topic-subject-new'],        
    ]))->commit();
}
 
foreach (TestTopic::retrieveAll(TestTopic::ID) as $topic) {
    if (isset($_POST["topic-name-{$topic->getId()}"]) && 
            ($_POST["topic-name-{$topic->getId()}"] != $topic->getName() ||
             $_POST["topic-subject-{$topic->getId()}"] != $topic->get(TestTopic::SUBJECT_ID))
            ) {
        (new TestTopic([
            TestTopic::ID => $topic->getId(),
            TestTopic::NAME => $_POST["topic-name-{$topic->getId()}"],
            TestTopic::SUBJECT_ID => $_POST["topic-subject-{$topic->getId()}"],
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
<thead><tr><th>Attach to subject</th><th>Topic</th></tr></thead>
<?php

$allSubjects = Subject::retrieveAll(Subject::NAME);

foreach (TestTopic::retrieveAll(TestTopic::NAME) as $topic) {
    echo "<tr>";
    echo "<td><select name=\"topic-subject-{$topic->getId()}\">";
    foreach ($allSubjects as $subject) {
        if ($subject->getId() == $topic->get(TestTopic::SUBJECT_ID)) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        echo "<option value=\"{$subject->getId()}\" $selected>{$subject->getName()}</option>"; 
    }
    echo "</select></td>";
    echo View::makeTextBoxCell("topic-name-" . $topic->getId(), $topic->getName());
    echo "</tr>";
}

echo "<tr>";
echo "<td><select name=\"topic-subject-new\">";
foreach ($allSubjects as $subject) {
    echo "<option value=\"{$subject->getId()}\">{$subject->getName()}</option>"; 
}
echo "</select></td>";
echo View::makeTextBoxCell("topic-name-new", "");
echo "</tr>";
?>
<tr><td colspan="2"><input class="form-control" type="submit" value="Save"></td></tr>
</table>
</form>
</div>
</body>
</html>