<?php
namespace TestAnalysis;

/* User authenticated? */

if(!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="CSE2K"');
    header('HTTP/1.0 401 Unauthorized');
}

$auth_user = preg_replace('/@' . Config::site_emaildomain . '/', "", $_SERVER['PHP_AUTH_USER']);

/* So, let's check this user should actually be here! */

/* Let's explicitly keep kids out, as staff regex may match kids! */
if (Config::is_student($auth_user) || !Config::is_staff($auth_user)) {
    echo "Sorry $auth_user, you're not able to use this.";
    die();
}

/* These are not the droids you have been looking for */