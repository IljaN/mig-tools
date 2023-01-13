#!/usr/bin/php
<?php

// Check if the script is run from the command line.
php_sapi_name() === 'cli' ?: exit(1);

if (count($argv) != 4) {
    echo "Usage: $argv[0] BATCH_PREFIX BATCH_COUNT INPUT_PATH\n";
    exit(1);
}

$batchCount = $argv[1];  // Number of batches to create
$batchPrefix = $argv[2]; // Prefix of the created bucket-files
$inputFile = $argv[3];   // Path to txt file which contains id:weight tuples separated by colon

// Read file which contains list of id:weight tuples by line(s) and split by id and weight
$lines = file($inputFile, FILE_IGNORE_NEW_LINES);
$list = [];
foreach ($lines as $line) {
    [$id, $weight] = explode(':', $line);
    $list[$id] = $weight;
}

// Sort by weight and discard the weights
asort($list);
$list = array_keys($list);

// Distribute to batches
$batches = [];
while (count($list) > 0) {
    for ($batchIdx = 0; $batchIdx < $batchCount; $batchIdx++) {
        $batches[$batchIdx][] = array_pop($list);
    }
}

// Write batches
foreach ($batches as $batchIdx => $bucket) {
    $fileName = $batchPrefix . '_batch_' . $batchIdx . '.txt';
    file_put_contents($fileName, implode(PHP_EOL, $bucket));
}
