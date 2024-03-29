<?php namespace TestAnalysis; ?>

<title><?= (isset($pageTitle) ? "$pageTitle - " : "") . Config::site ?></title>

<link rel="icon" href="<?= Config::site_url; ?>/img/badge_blue.png">

<?php
// Sort themes

$staff = $staff ?? Staff::me($auth_user);

if (!is_null($staff)) {
    if (isset($_GET['theme'])) {
        switch ($_GET['theme']) {
        case 'darkly':
            $staff->setTheme('darkly');
            $staff->commit();
            break;
        default:
            $staff->commit([Staff::THEME]);
            break;
        }
    }
    if (isset($_GET['large_marking'])) {
        $staff->setLargeMarking($_GET['large_marking'] == 0 ? 0 : 1);
        $staff->commit();
    }
    if (isset($_GET['default_marking_tool'])) {
        $staff->setDefaultMarkingTool($_GET['default_marking_tool']);
        $staff->commit();
    }
    if (isset($_GET['mark_by_student'])) {
        $staff->setMarkByStudent($_GET['mark_by_student']);
        $staff->commit();
    }
}

if (!is_null($staff) && !is_null($staff->get(Staff::THEME))) {
    echo '<link rel="stylesheet" href="' . Config::site_url . '/css/themes/' . $staff->get(Staff::THEME) . '-custom.css">';
    echo '<link rel="stylesheet" href="' . Config::site_url . '/css/themes/' . $staff->get(Staff::THEME) . '.css">';
} else {
    echo '<link rel="stylesheet" href="' . Config::site_url . '/css/bootstrap-custom.css">';
    echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">';
}
?>

<link rel="stylesheet" href="<?=Config::site_url;?>/css/custom.css">
<link rel="stylesheet" href="<?=Config::site_url;?>/contrib/djaodjin-annotate/css/annotate.css">

<script src="https://kit.fontawesome.com/87d5e15aad.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
<script src="<?=Config::site_url;?>/contrib/djaodjin-annotate/djaodjin-annotate.js"></script>

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">