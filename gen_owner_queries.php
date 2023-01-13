#!/usr/bin/php
<?php

if (php_sapi_name() != 'cli') {
    die('This script can only be run from the command line.');
}

if (count($argv) != 2) {
    die('Usage: gen_owner_queries.php <path> > output.sql');
}

$path = $argv[1];

if (!is_file($path)) {
    die('Not file or does not exist.');
}

$records = parseCsv($path);
foreach($records as $record) {
    echo generateInsertsSQL($record) . PHP_EOL;
}


// format of the csv file: "spaceName, missingOwners, spaceId"
function parseCsv($file) : array {
    $csv = fopen($file, "r");
    $header = fgetcsv($csv);
    $result = array();
    while ($row = fgetcsv($csv)) {
        $record = array_combine($header, $row);
        $record['missingOwners'] = explode(",", $record['missingOwners']);
        $result[] = $record;
    }
    fclose($csv);
    return $result;
}

function generateInsertsSQL(array $record) : string {
    $spaceId = $record['spaceId'];
    $spaceName  = $record['spaceName'];
    $missingOwners = $record['missingOwners'];

    $inserts = "-- $spaceName ($spaceId):" . PHP_EOL;
    foreach ($missingOwners as $missingOwner) {
        $inserts .= "INSERT INTO oc_files_spaces_access (space_id, user_id, `role`) VALUES ($spaceId, '$missingOwner', 100);" . PHP_EOL;
    }

    return $inserts;
}

