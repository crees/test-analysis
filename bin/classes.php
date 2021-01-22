<?php

namespace TestAnalysis;
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

/* Have we been called before? */
if (!class_exists("Config")) {
    require "Config.php";
    require "Database.php";
    require "TempFile.php";
    
    require "DatabaseCollection.php";
    require "Baseline.php";
    require "Department.php";
    require "FeedbackSheet.php";
    require "GradeBoundary.php";
    require "GraphQLClient.php";
    require "ScannedTest.php";
    require "ScannedTestPage.php";
    require "Staff.php";
    require "StaffDepartmentMembership.php";
    require "Student.php";
    require "Subject.php";
    require "TeachingGroup.php";
    require "Test.php";
    require "TestComponent.php";
    require "TestComponentResult.php";
    require "TestSubjectMembership.php";
    require "TestTopic.php";
    require "TestTestTopic.php";
    require "View.php";
    require Config::site_docroot . "/contrib/docxmerge/vendor/autoload.php";
    require Config::site_docroot . "/contrib/php-graphql-client/vendor/autoload.php";
    
    require Config::site_docroot . "/dev/upgrade_database.php";
    
    /**
     * We start the session timer on creation, and destroy it after that time.
     */
    $timeout_duration = 6400;
    
    ini_set('session.cookie_samesite', 'Strict');
    
    session_name("TestAnalysis");
    
    session_start(['gc_maxlifetime' => $timeout_duration, 'cookie_lifetime' => $timeout_duration]);
    
    $time = $_SERVER['REQUEST_TIME'];
    
    if (!isset($_SESSION['SESSION_CREATIONTIME']) ||
        ($time - $_SESSION['SESSION_CREATIONTIME']) > $timeout_duration ||
        (isset($_GET['session_destroy']) && $_GET['session_destroy'] == $_SESSION['SESSION_CREATIONTIME'])) {
            session_unset();
            session_destroy();
            session_start();
    }
    $_SESSION['SESSION_CREATIONTIME'] = $time;
    if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") {
        header('HTTP/1.1 301 Moved Permanently');
        header('location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit();
    }
    
    if (!isset($_SESSION['form_serial'])) {
        $_SESSION['form_serial'] = 0;
    }
    $_SESSION['form_serial']++;
    
    require "auth.php";
}
