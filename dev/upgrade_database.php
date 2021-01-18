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

/* Version 5 to 6 upgrade */
if ($db->dosql("SHOW COLUMNS FROM `ScannedTest` LIKE 'ts_unlocked'")->num_rows < 1) {
    $db->dosql("ALTER TABLE `ScannedTest` ADD ts_unlocked INT NOT NULL DEFAULT 0;");
}

/* Version 6 to 7 upgrade */

if ($db->dosql("SHOW TABLES LIKE 'Department'")->num_rows < 1) {
    $db->dosql("CREATE TABLE Department (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(30) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
        );");
    
    $db->dosql("ALTER TABLE Subject ADD Department_id INT NOT NULL;");
    
    $db->dosql("UPDATE Subject SET Department_id = 1;");
}

/* Version 7 to 8 upgrade */

if ($db->dosql("SHOW COLUMNS FROM `Test` LIKE 'Department_id'")->num_rows < 1) {
    $db->dosql("ALTER TABLE `Test` ADD Department_id INT NOT NULL DEFAULT 0;");
}

/* Version 8 to 9 upgrade */

if ($db->dosql("SHOW TABLES LIKE 'TestComponent'")->num_rows < 1) {
    /* Create testcomponents table */
    $db->dosql("CREATE TABLE TestComponent (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(10) NULL,
        Test_id INT NOT NULL,
        total INT NOT NULL,
        included_in_percent BOOLEAN NOT NULL DEFAULT FALSE,
        included_in_grade BOOLEAN NOT NULL DEFAULT FALSE,
        included_for_targets BOOLEAN NOT NULL DEFAULT FALSE,
        CONSTRAINT PRIMARY KEY (id)
        );");
    $db->dosql("CREATE TABLE TestComponentResult (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Student_id INT NOT NULL,
        TestComponent_id INT NOT NULL,
        score INT NOT NULL,
        recorded_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT PRIMARY KEY (id)
        );");
    foreach ($db->dosql("SELECT id, name, total_a, total_b FROM `Test`;")->fetch_all() as $test) {
        if ($test[2] == 0) {
            // One section test
            $db->dosql("INSERT INTO `TestComponent` (Test_id, name, total, included_in_percent, included_in_grade, included_for_targets) VALUES ({$test[0]}, NULL, {$test[3]}, TRUE, TRUE, TRUE);");
            $testComponentId = $db->dosql("SELECT id FROM `TestComponent` WHERE Test_id = {$test[0]};")->fetch_all()[0][0];
            foreach ($db->dosql("SELECT score_a, score_b, Student_id, recorded_ts FROM `TestResult` WHERE Test_id = '{$test[3]}';")->fetch_all() as $result) {
                if (is_null($result)) {
                    continue;
                }
                $db->dosql("INSERT INTO `TestComponentResult` (Student_id, TestComponent_id, score, recorded_ts) VALUES ({$result[2]}, $testComponentId, {$result[1]}, '{$result[3]}');");
            }
        } else {
            // Sections A and B
            $db->dosql("INSERT INTO `TestComponent` (Test_id, name, total, included_in_percent) VALUES ({$test[0]}, 'A', {$test[2]}, TRUE);");
            $db->dosql("INSERT INTO `TestComponent` (Test_id, name, total, included_in_grade, included_for_targets) VALUES ({$test[0]}, 'B', {$test[3]}, TRUE, TRUE);");
            foreach ($db->dosql("SELECT score_a, score_b, Student_id, recorded_ts FROM `TestResult` WHERE Test_id = '{$test[0]}';")->fetch_all() as $result) {
                print_r($result);
                if (is_null($result)) {
                    continue;
                }
                $testComponents = $db->dosql("SELECT id, name FROM `TestComponent` WHERE Test_id = {$test[0]};")->fetch_all();
                if ($testComponents[0][1] == 'A') {
                    $testComponentAId = $testComponents[0][0];
                    $testComponentBId = $testComponents[1][0];
                } else {
                    $testComponentAId = $testComponents[1][0];
                    $testComponentBId = $testComponents[0][0];
                }
                $db->dosql("INSERT INTO `TestComponentResult` (Student_id, TestComponent_id, score, recorded_ts) VALUES ({$result[2]}, $testComponentAId, {$result[0]}, '{$result[3]}');");
                $db->dosql("INSERT INTO `TestComponentResult` (Student_id, TestComponent_id, score, recorded_ts) VALUES ({$result[2]}, $testComponentBId, {$result[1]}, '{$result[3]}');");
            }
        }
    }
    $db->dosql("DROP TABLE `TestResult`;");
    $db->dosql("ALTER TABLE `Test` DROP COLUMN total_a;");
    $db->dosql("ALTER TABLE `Test` DROP COLUMN total_b;");
}

/* Version 9 to 10 upgrade */
if ($db->dosql("SHOW COLUMNS FROM `ScannedTest` LIKE 'Subject_id'")->num_rows < 1) {
    $db->dosql("ALTER TABLE `ScannedTest` ADD Subject_id INT NOT NULL DEFAULT 22;");
}

/* Version 10 to 11 upgrade */

if ($db->dosql("SHOW COLUMNS FROM `ScannedTestPage` LIKE 'TestComponent_id'")->num_rows < 1) {
    $db->dosql("ALTER TABLE `ScannedTestPage` ADD TestComponent_id INT NULL;");
}

/* Version 12 to 13 upgrade (enclosed v11 upgrade too) */

if ($db->dosql("SHOW TABLES LIKE 'Staff'")->num_rows < 1) {

    $db->dosql("ALTER TABLE `Baseline` MODIFY grade VARCHAR(30) NOT NULL;"); // v12

    $db->dosql("CREATE TABLE Staff (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        arbor_id INT UNSIGNED NOT NULL,
        first_name VARCHAR(30) NOT NULL,
        last_name VARCHAR(30) NOT NULL,
        username VARCHAR(30) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
        );");
    
    $db->dosql("ALTER TABLE `TestComponentResult` ADD Staff_id INT NOT NULL;");
    
    $db->dosql("ALTER TABLE `ScannedTest` ADD Staff_id INT NOT NULL;");
}

if ($db->dosql("SHOW TABLES LIKE 'FeedbackSheet'")->num_rows < 1) {
    
    $db->dosql("ALTER TABLE `Subject` ADD FeedbackSheet_id INT UNSIGNED NULL;"); // v12
    
    $db->dosql("CREATE TABLE FeedbackSheet (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(30) NOT NULL,
        templatedata MEDIUMBLOB NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
        );");
}

if ($db->dosql("SHOW COLUMNS FROM `ScannedTest` LIKE 'student_upload_allowed'")->num_rows < 1) {
    $db->dosql("ALTER TABLE `ScannedTest` ADD student_upload_allowed INT NOT NULL DEFAULT 0;");
}
