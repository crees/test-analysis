<?php
namespace testanalysis;

$db = new Database();

if ($db->dosql("SHOW TABLES LIKE 'db_version'")->num_rows == 1) {
    $dbVersion = $db->dosql("SELECT version FROM `db_version`;")->fetch_row()[0];
} else {
    $dbVersion = 0;
}

// Fallthrough in all cases
switch ($dbVersion) {
case 0:
case 1:
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
    
case 2:
    // Need a much longer TeachingGroup name- harmless so let's just do it anyway.
    
    $db->dosql("ALTER TABLE `TeachingGroup` MODIFY name VARCHAR(100) NOT NULL;");
    
case 3:
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
    
case 4:
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
    
case 5:
    if ($db->dosql("SHOW COLUMNS FROM `ScannedTest` LIKE 'ts_unlocked'")->num_rows < 1) {
        $db->dosql("ALTER TABLE `ScannedTest` ADD ts_unlocked INT NOT NULL DEFAULT 0;");
    }
    
case 6:    
    if ($db->dosql("SHOW TABLES LIKE 'Department'")->num_rows < 1) {
        $db->dosql("CREATE TABLE Department (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(30) NOT NULL,
            CONSTRAINT PRIMARY KEY (id)
            );");
        
        $db->dosql("ALTER TABLE Subject ADD Department_id INT NOT NULL;");
        
        $db->dosql("UPDATE Subject SET Department_id = 1;");
    }
    
case 7:    
    if ($db->dosql("SHOW COLUMNS FROM `Test` LIKE 'Department_id'")->num_rows < 1) {
        $db->dosql("ALTER TABLE `Test` ADD Department_id INT NOT NULL DEFAULT 0;");
    }
    
case 8:    
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
    
case 9:
    if ($db->dosql("SHOW COLUMNS FROM `ScannedTest` LIKE 'Subject_id'")->num_rows < 1) {
        $db->dosql("ALTER TABLE `ScannedTest` ADD Subject_id INT NOT NULL DEFAULT 22;");
    }
    
case 10:
    if ($db->dosql("SHOW COLUMNS FROM `ScannedTestPage` LIKE 'TestComponent_id'")->num_rows < 1) {
        $db->dosql("ALTER TABLE `ScannedTestPage` ADD TestComponent_id INT NULL;");
    }
    
case 11:
case 12:
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
    
case 13:
    if ($db->dosql("SHOW TABLES LIKE 'FeedbackSheet'")->num_rows < 1) {
        
        $db->dosql("ALTER TABLE `Subject` ADD FeedbackSheet_id INT UNSIGNED NULL;"); // v12
        
        $db->dosql("CREATE TABLE FeedbackSheet (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(30) NOT NULL,
            templatedata MEDIUMBLOB NOT NULL,
            CONSTRAINT PRIMARY KEY (id)
            );");
    }
    
case 14:
    if ($db->dosql("SHOW COLUMNS FROM `ScannedTest` LIKE 'student_upload_allowed'")->num_rows < 1) {
        $db->dosql("ALTER TABLE `ScannedTest` ADD student_upload_allowed INT NOT NULL DEFAULT 0;");
    }

case 15:
    if ($db->dosql("SHOW COLUMNS FROM `Staff` LIKE 'theme'")->num_rows < 1) {
        $db->dosql("ALTER TABLE `Staff` ADD theme VARCHAR(30) NULL;");
    }
    
    $db->dosql("UPDATE `db_version` SET version = 16;");
case 16:
    $db->dosql("CREATE TABLE db_version (version INT UNSIGNED NOT NULL);");
    $db->dosql("INSERT INTO `db_version` (version) VALUES (17);");

    $db->dosql("UPDATE `db_version` SET version = 17;");
case 17:
    $db->dosql("CREATE TABLE StaffDepartmentMembership (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            Staff_id INT NOT NULL,
            Department_id INT NOT NULL,
            department_admin INT NOT NULL DEFAULT 0,
            CONSTRAINT PRIMARY KEY (id)
        );");
    $db->dosql("ALTER TABLE `Staff` ADD global_admin INT NOT NULL DEFAULT 0;");
    $db->dosql("UPDATE `Staff` SET global_admin = 1 WHERE Staff.id = 1");
    foreach ($db->dosql("SELECT Staff.id FROM Staff;")->fetch_all() as $staffId) {
        foreach ($db->dosql("SELECT Department.id FROM Department;")->fetch_all() as $deptId) {
            $db->dosql("INSERT INTO StaffDepartmentMembership (Staff_id, Department_id) VALUES ({$staffId[0]}, {$deptId[0]});");
        }
    }
    
    $db->dosql("UPDATE `db_version` SET version = 18;");
case 18:
    $db->dosql("ALTER TABLE `Test` MODIFY name VARCHAR(100) NOT NULL;");
    
    $db->dosql("UPDATE `db_version` SET version = 19;");
case 19:
    $db->dosql("ALTER TABLE `Student` ADD username VARCHAR(30) NULL;");
    
    $db->dosql("UPDATE `db_version` SET version = 20;");
case 20:
    $db->dosql("ALTER TABLE `Staff` ADD large_marking TINYINT NOT NULL DEFAULT 0;");
    
    $db->dosql("UPDATE `db_version` SET version = 21;");
case 21:
    $db->dosql("ALTER TABLE `ScannedTest` ADD downloaded TINYINT NOT NULL DEFAULT 0;");
    
    $db->dosql("UPDATE `db_version` SET version = 22;");
case 22:
    $db->dosql("ALTER TABLE `Subject` DROP code;");
    
    $db->dosql("UPDATE `db_version` SET version = 23;");
case 23:
    $db->dosql("ALTER TABLE `Staff` ADD default_marking_tool VARCHAR(20) NULL;");
    
    $db->dosql("UPDATE `db_version` SET version = 24;");
case 24:
    if ($db->dosql("SHOW COLUMNS FROM `ScannedTestPage` LIKE 'sha'")->num_rows < 1)
        $db->dosql("ALTER TABLE `ScannedTestPage` ADD sha CHAR(64) NULL;");
    $db->dosql("UPDATE `db_version` SET version = 25;");
    
case 25:
    $limit = $version_25_limit ?? 20;
    $query = $db->dosql("SELECT `id`,`imagedata` FROM `ScannedTestPage` WHERE `sha` IS NULL LIMIT $limit;");
    if ($query->num_rows != 0) {
        while ($i = $query->fetch_row()) {
            $id = $i[0];
            $img = $i[1];
            $sha = hash('sha256', $img);
            $filename = Config::scannedTestPagedir . "/$sha.jpg";
            
            if ($file = @fopen($filename, 'xb')) {
                fwrite($file, $img);
                fclose($file);
            }
            
            if (!file_exists($filename)) {
                // BIG PROBLEM
                throw new \Exception('File creation failed-- does ' .
                    Config::scannedTestPagedir . ' exist and can I write to it?');
            }
            
            $db->dosql("UPDATE `ScannedTestPage` SET `sha` = '$sha', `imagedata` = NULL WHERE `id` = $id;");
        }
    } else {
        $db->dosql("ALTER TABLE `ScannedTestPage` DROP COLUMN `imagedata`;");
        $db->dosql("UPDATE `db_version` SET version = 26;");
    }
    
case 26:
    $db->dosql("ALTER TABLE `TestComponentResult` ADD inactive INT(1) NOT NULL DEFAULT 0;");
    $db->dosql("UPDATE `db_version` SET version = 27;");
    
case 27:
    $db->dosql("ALTER TABLE `Student` ADD gender CHAR(1) NOT NULL;");
    $db->dosql("CREATE TABLE `Demographic` (
            id INT NOT NULL AUTO_INCREMENT,
            Student_id INT NOT NULL,
            mis_id INT NOT NULL,
            tag INT NOT NULL,
            detail VARCHAR(255) NOT NULL,
            CONSTRAINT PRIMARY KEY (id)
        );");
    $db->dosql("UPDATE `db_version` SET version = 28;");
    
case 28:
    $db->dosql("ALTER TABLE `Staff` ADD mark_by_student TINYINT NOT NULL DEFAULT 0;");

    $db->dosql("UPDATE `db_version` SET version = 29;");

case 29:
    $db->dosql("ALTER TABLE `StudentGroupMembership`
        ADD `touched_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");

    $db->dosql("UPDATE `db_version` SET version = 30;");

case 30:
    
    $db->dosql("ALTER TABLE `TestComponent`
        ADD `included_in_regression` BOOLEAN NOT NULL DEFAULT FALSE;");
    
    $db->dosql("UPDATE `db_version` SET version = 31;");
    
case 31:

    $db->dosql("CREATE TABLE `TestRegression` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `Test_id` INT NOT NULL,
            `regression_key` VARCHAR (30) NOT NULL,
            `regression_gradient` FLOAT NOT NULL,
            `regression_intercept` FLOAT NOT NULL,
            CONSTRAINT PRIMARY KEY (`id`)
        );");
    
    $db->dosql("UPDATE `db_version` SET version = 32;");
    
case 32:
    
    $db->dosql("ALTER TABLE `TestRegression`
        ADD `regression_error` INT NULL;");
    
    $db->dosql("UPDATE `db_version` SET version = 33;");
    
default:

}