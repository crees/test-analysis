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

/* Version 2 to 3 upgrade */
// Need a much longer TeachingGroup name- harmless so let's just do it anyway.

$db->dosql("ALTER TABLE `TeachingGroup` MODIFY name VARCHAR(100) NOT NULL;");

/* Version 3 to 4 upgrade */
// Need somewhere to store uploaded tests

if ($db->dosql("SHOW TABLES LIKE 'ScannedTestPage'")->num_rows < 1) {
    $db->dosql("CREATE TABLE ScannedTestPage (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        page_num INT NOT NULL,
        Test_id INT NOT NULL,
        Student_id INT NOT NULL,
        imagedata MEDIUMBLOB NULL,
        annotations BLOB NULL,
        page_score INT NULL,
        CONSTRAINT PRIMARY KEY (id)
        );");
}

/* Version 4 to 5 upgrade */
if ($db->dosql("SHOW COLUMNS FROM `ScannedTestPage` LIKE 'student_annotations'")->num_rows < 1) {
    $db->dosql("CREATE TABLE ScannedTest (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Test_id INT NOT NULL,
        Student_id INT NOT NULL,
        minutes_allowed INT NULL,
        ts_started INT NULL,
        CONSTRAINT PRIMARY KEY (id)
        );");
    $db->dosql("ALTER TABLE ScannedTestPage ADD student_annotations BLOB NULL;");
}