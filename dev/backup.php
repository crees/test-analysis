<?php

namespace TestAnalysis;

// Need to call this as /dev/backup.php?backupkey=xxxxxxx where backupkey is set in Config.php
$backup_key_override_auth = true;

require '../bin/classes.php';

function rfccsv($arr){
    foreach($arr as &$a){
        $a=strval($a);
        if(strcspn($a,",\"\r\n")<strlen($a))$a='"'.strtr($a,array('"'=>'""')).'"';
    }
    return implode(',',$arr) . "\n";
}

// We don't back up students for easy data protection and they are always available from Arbor

$date = date('Y-m-d');

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=testanalysis-$date.csv");
header("Pragma: no-cache");
header("Expires: 0");

$output = "Test Analysis Database version: {$db->dosql("SELECT version FROM `db_version`;")->fetch_row()[0]}\n\n";

$tables = ['Department', 'Staff', 'StaffDepartmentMembership', 'Subject', 'Test', 'TestComponent', 'TestSubjectMembership', 'TestComponentResult', 'GradeBoundary'];

$db = new Database();

foreach ($tables as $table) {
    $output .= "$table\n\n";
    $cols = $db->dosql("show columns from `$table`;")->fetch_all(MYSQLI_ASSOC);
    $fields = [];
    
    foreach ($cols as $c) {
        array_push($fields, $c['Field']);
    }
    
    $output = $output . rfccsv($fields);
    
    foreach ($db->dosql("SELECT * FROM `$table`;")->fetch_all(MYSQLI_ASSOC) as $row) {
        $line = [];
        foreach ($fields as $f) {
            array_push($line, $row[$f]);
        }
        $output = $output . rfccsv($line);
    }
    
    $output .= "\n\n";
}

die($output);