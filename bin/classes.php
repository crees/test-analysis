<?php

namespace TestAnalysis;

/* Have we been called before? */
if (!class_exists("Config")) {
    require "Config.php";
    require "Database.php";
    require "DatabaseCollection.php";
    require "GraphQLClient.php";
    require "Student.php";
    require "Subject.php";
    require "TeachingGroup.php";
    require "Test.php";
    require "TestResult.php";
    require "View.php";
    require Config::site_docroot . "/contrib/php-graphql-client/vendor/autoload.php";
    
    /**
     * We start the session timer on creation, and destroy it after that time.
     * We don't allow keepalive or the data will become stale.
     */
    $timeout_duration = 600;
    
    session_start(['gc_maxlifetime' => $timeout_duration, 'cookie_lifetime' => $timeout_duration]);
    
    $time = $_SERVER['REQUEST_TIME'];
    
    if (!isset($_SESSION['SESSION_CREATIONTIME']) ||
        ($time - $_SESSION['SESSION_CREATIONTIME']) > $timeout_duration ||
        (isset($_GET['session_destroy']) && $_GET['session_destroy'] == $_SESSION['SESSION_CREATIONTIME'])) {
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['SESSION_CREATIONTIME'] = $time;
    }
    if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") {
        header('HTTP/1.1 301 Moved Permanently');
        header('location: ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit();
    }
    
    if (!isset($_SESSION['form_serial'])) {
        $_SESSION['form_serial'] = 0;
    }
    $_SESSION['form_serial']++;
    
}
