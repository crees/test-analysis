<?php
namespace TestAnalysis;

function auth_generate_key($prefix) {
    return hash("sha256", $prefix . ";key:" . Config::secret_key);
}

/* User authenticated? */

/* Skip auth if cron.php */

if (isset($_GET['backupkey']) && $_GET['backupkey'] == Config::backups_key && isset($backup_key_override_auth)) {
    // No need to auth, special case for backups
    if (empty(Config::backups_key)) {
        die('You must first set a backup key in Config.php');
    }
} else if (isset($auth_key_prefix) && isset($_GET['key'])) {
    if (auth_generate_key($auth_key_prefix) == $_GET['key']) {
        $key_authed = true;
    } else {
        die('Auth key does not match');
    }
} else if (!isset($auth_skip)){
    if(!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Username in lower case-- no capitals"');
        header('HTTP/1.0 401 Unauthorized');
        die();
    }
    
    $auth_user = preg_replace('/@' . strtolower(Config::site_emaildomain) . '/', "", strtolower($_SERVER['PHP_AUTH_USER']));
    
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
    } elseif (Config::is_staff($auth_user)) {
        if (!isset($_SESSION['staff']) || $_SESSION['staff']->get(Staff::USERNAME) != $auth_user) {
            // Look up staff, and if they don't exist then use Arbor
            try {
                $staff = Staff::me($auth_user);
                $arborId = $staff->get(Staff::ARBOR_ID);
            } catch (\Exception $e) {
                $arborId = 0;
            }
            if ($arborId == 0) {
                $details = [];
                $emailAddress = $auth_user . "@" . Config::site_emaildomain;
                $emailQuery = "{ EmailAddress (emailAddress: \"$emailAddress\") { emailAddressOwner { ... on Staff { id firstName lastName entityType } } } }";
                $client = new GraphQLClient();
                try {
                    $qEmailAddress = $client->rawQuery($emailQuery)->getData()['EmailAddress'];
                } catch (\Exception $e) {
                    die("<h3>Sorry, Arbor has not responded to finding out who you are.  Please try refreshing.</h3>");
                }
                Config::debug("Staff::__construct: query complete");
                if (!isset($qEmailAddress[0])) {
                    die("Your email address $emailAddress appears unrecognised.");
                }
                if (isset($qEmailAddress[1])) {
                    die("Your email address appears to have more than one owner.  This cannot possibly be right");
                }
                if ($qEmailAddress[0]['emailAddressOwner']['entityType'] != 'Staff') {
                    die("Your email address $emailAddress appears not to belong to a member of staff.");
                }
                $details[Staff::ID] = null;
                $details[Staff::ARBOR_ID] = $qEmailAddress[0]['emailAddressOwner']['id'];
                $details[Staff::FIRST_NAME] = $qEmailAddress[0]['emailAddressOwner']['firstName'];
                $details[Staff::LAST_NAME] = $qEmailAddress[0]['emailAddressOwner']['lastName'];
                $details[Staff::USERNAME] = $auth_user;
                if (isset($staff)) {
                    $staff->updateDetails($details);
                } else {
                    $staff = new Staff($details);
                }
                $staff->commit();
            }
            $_SESSION['staff'] = $staff;
            
            if (empty($staff->getDepartments()) && !$staff->isGlobalAdmin()) {
                die("Please get in touch with your head of department to give you access.");
            }
        }
        switch (basename(dirname($_SERVER['PHP_SELF']))) {
        case 'dev':
            // Lock non-admins out of management area
            if (!$staff->isDepartmentAdmin()) {
                header("location: " . Config::site_url . "/index.php");
                die();
            }
            break;
        default:
            // Fallthrough
        }
    } else {
        echo "Sorry $auth_user, you're not able to use this.";
        die();
    }
}
/* These are not the droids you have been looking for */