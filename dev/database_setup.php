<?php
namespace TestAnalysis;
require "../bin/classes.php";

$db = new Database(TRUE);
$dbname = Config::db['name'];

$db->dosql("USE $dbname;", FALSE);

if (isset(Config::$maintenance) && Config::$maintenance) {
    $db->dosql("DROP DATABASE $dbname;", FALSE); /* Don't mind if this fails */
    $db->dosql("CREATE DATABASE $dbname;");
    $db->dosql("USE $dbname;");
    $db->dosql("
    CREATE TABLE Baseline (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(300) NOT NULL,
        Mis_assessment_id INT UNSIGNED NOT NULL,
        Student_id INT UNSIGNED NOT NULL,
        grade VARCHAR(10) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );");
    $db->dosql("
    CREATE TABLE Subject (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(30) NOT NULL,
        code VARCHAR(2) NOT NULL,
        Baseline_id INT UNSIGNED DEFAULT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );");
    $db->dosql("
    CREATE TABLE TeachingGroup (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(30) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );");
    $db->dosql("
    CREATE TABLE GroupSubjectMembership (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Subject_id INT NOT NULL,
        TeachingGroup_id INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );");
    $db->dosql("
    CREATE TABLE Student (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(30) NOT NULL,
        last_name VARCHAR(30) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );");
    $db->dosql("
    CREATE TABLE StudentGroupMembership (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Student_id INT NOT NULL,
        TeachingGroup_id INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );");
    $db->dosql("
    CREATE TABLE Test (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Subject_id INT NOT NULL,
        name VARCHAR(30) NOT NULL,
        total_a INT NOT NULL,
        total_b INT NOT NULL,
        custom_grade_boundaries BOOLEAN NOT NULL DEFAULT FALSE,
        CONSTRAINT PRIMARY KEY (id)
    );");
    $db->dosql("
    CREATE TABLE GradeBoundary (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Test_id INT NOT NULL,
        name VARCHAR(30) NOT NULL,
        boundary SMALLINT,
        CONSTRAINT PRIMARY KEY (id)
    );");
    $db->dosql("
    CREATE TABLE TestResult (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Student_id INT NOT NULL,
        Test_id INT NOT NULL,
        recorded_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        score_a INT NOT NULL,
        score_b INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );");
}
?>