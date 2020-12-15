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
    $db->dosql( <<< EOF
    CREATE TABLE Baseline (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(300) NOT NULL,
        Mis_assessment_id INT UNSIGNED NOT NULL,
        Student_id INT UNSIGNED NOT NULL,
        grade VARCHAR(10) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE Department (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(30) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE GradeBoundary (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Test_id INT NOT NULL,
        name VARCHAR(30) NOT NULL,
        boundary SMALLINT,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE Student (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(30) NOT NULL,
        last_name VARCHAR(30) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE Subject (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(30) NOT NULL,
        code VARCHAR(2) NOT NULL,
        Department_id INT NOT NULL,
        Baseline_id INT UNSIGNED DEFAULT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE TeachingGroup (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        academic_year VARCHAR(20) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE GroupSubjectMembership (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Subject_id INT NOT NULL,
        TeachingGroup_id INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE StudentGroupMembership (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Student_id INT NOT NULL,
        TeachingGroup_id INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE Test (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(30) NOT NULL,
        custom_grade_boundaries BOOLEAN NOT NULL DEFAULT FALSE,
        targets VARCHAR(65000) NULL,
        Department_id INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE TestComponent (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(10) NOT NULL,
        Test_id INT NOT NULL,
        total INT NOT NULL,
        included_in_percent BOOLEAN NOT NULL DEFAULT FALSE,
        included_in_grade BOOLEAN NOT NULL DEFAULT FALSE,
        included_for_targets BOOLEAN NOT NULL DEFAULT FALSE,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE TestComponentResult (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Student_id INT NOT NULL,
        TestComponent_id INT NOT NULL,
        score INT NOT NULL,
        recorded_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT PRIMARY KEY (id)
        );
    CREATE TABLE TestSubjectMembership (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Test_id INT NOT NULL,
        Subject_id INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE TestTopic (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Subject_id INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
    CREATE TABLE TestTestTopic (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        Test_id INT NOT NULL,
        TestTopic_id INT NOT NULL,
        CONSTRAINT PRIMARY KEY (id)
    );
EOF);
}
?>