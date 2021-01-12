<?php
namespace TestAnalysis;

/* User authenticated? */

if(!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Username in lower case-- no capitals"');
    header('HTTP/1.0 401 Unauthorized');
}

$auth_user = preg_replace('/@' . Config::site_emaildomain . '/', "", $_SERVER['PHP_AUTH_USER']);

/* So, let's check this user should actually be here! */

/* Redirect students if necessary */
if (Config::is_student($auth_user)) {
    switch (basename(dirname($_SERVER['PHP_SELF']))) {
    case 'students':
        break;
    case 'async':
        if (isset($students_allowed) && $students_allowed === true) {
            break;
        }
        /* FALLTHROUGH - students_allowed not set so they can't use it */
    default:
        header("location: " . Config::site_url . "/students/index.php");
        die();
    }
} elseif (!Config::is_staff($auth_user)) {
    echo "Sorry $auth_user, you're not able to use this.";
    die();
}

/* These are not the droids you have been looking for */