<?php
namespace testanalysis;

$db = new Database();

/* Version 1 to 2 upgrade */
if ($db->dosql("SHOW COLUMNS FROM `Test` LIKE 'Subject_id'")->num_rows > 0) {
    $mappings = $db->dosql("SELECT ID, Subject_id FROM `Test`")->fetch_all();
    $db->dosql("CREATE TABLE TestSubjectMembership (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Test_id INT NOT NULL,
        Subject_id INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );");
    
    foreach ($mappings as $m) {
        $test = $m[0];
        $subject = $m[1];
        $db->dosql("INSERT INTO `TestSubjectMembership` (Test_id, Subject_id) VALUES (\"$test\", \"$subject\");");
    }
    $db->dosql("ALTER TABLE `Test` DROP COLUMN Subject_id;");
    $db->dosql("ALTER TABLE `Subject` DROP COLUMN num_targets;");
}