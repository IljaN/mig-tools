#!/usr/bin/php
<?php

if (php_sapi_name() != 'cli') {
    die('This script can only be run from the command line.');
}

if (count($argv) != 2) {
    die('Usage: fix_reports.php cleaner.php <path>');
}

$path = $argv[1];

if (!is_dir($path)) {
    die('Path is not a directory.');
}


$missingOwnersReports = array();
iterate_csv_files($path, function ($file) use (&$missingOwnersReports) {
    $newName = fixWhiteSpace($file);
    if ($newName !== $file) {
        echo "Renamed $file to $newName" . PHP_EOL;
        $file = $newName;
    }

    $prettyName = str_replace(".csv", "", \basename($file));
    $removedOwners =  removeAllButFirstOwner($file);
    if (!empty($removedOwners)) {
        $removedStr = implode(",", $removedOwners);
        $missingOwnersReports[] = ["spaceName" => $prettyName, "missingOwners" => $removedOwners];
        echo "Removed owners [$removedStr] from $prettyName".PHP_EOL;
    }
});


// Save $missingOwnersReports to a csv file
$csv = fopen("$path/missing_owners.csv", "w");
fputcsv($csv, ["spaceName", "missingOwners"]);
foreach ($missingOwnersReports as $report) {
    $report['missingOwners'] = implode(",", $report['missingOwners']);
    fputcsv($csv, $report);
}

fclose($csv);




// function to parse a csv file with the following format: "path,share-type,shared-by,shared-with,permissions" and remove every
// line which has the value "owner" in the column "share-type" except the first one. Return the "shared-by" value of the removed owner-lines
// as array.
function removeAllButFirstOwner($file) : array {
    $lines = file($file);
    $newLines = array();
    $firstOwner = true;
    $removedOwners = array();
    foreach ($lines as $line) {
        if (strpos($line, '-,owner') !== false) {
            if ($firstOwner) {
                $newLines[] = $line;
                $firstOwner = false;
            } else {
                $removedOwners[] = explode(',', $line)[2];
            }
        } else {
            $newLines[] = $line;
        }
    }
    file_put_contents($file, $newLines);
    return $removedOwners;
}


// function to remove all whitespace before the file extension
function fixWhiteSpace($file) : string {
    $newName = preg_replace('/\s+(?=\.\w+$)/', '', $file);
    if ($newName !== $file) {
        rename($file, $newName);
    }
    return $newName;
}


// Function which iterates over all csv files in the given directory. It should receive a callback function which will be called for each file.
function iterate_csv_files($path, $callback) {
    $dir = new RecursiveDirectoryIterator($path);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, '/^.+\.csv$/i', RecursiveRegexIterator::GET_MATCH);
    foreach ($files as $file) {
        $callback($file[0]);
    }
}


