#!/usr/bin/php
<?php

if ($argc < 3) {
    echo "Usage: php oc_logmerge.php owncloud.log [audit.log ...]\n";
    exit(1);
}

// Open or create SQLite database
$outputDb = new SQLite3($argv[1]);
$outputDb->exec('CREATE TABLE IF NOT EXISTS logs (time TEXT, log_json TEXT)');
$outputDb->exec('CREATE INDEX IF NOT EXISTS time_index ON logs (time)');
$outputDb->exec('PRAGMA synchronous = OFF');
$outputDb->exec('PRAGMA journal_mode = MEMORY');

// Function to insert log entry into database
function insertLog($db, $time, $json)
{
    $stmt = $db->prepare('INSERT INTO logs (time, log_json) VALUES (:time, :json)');
    $stmt->bindValue(':time', $time, SQLITE3_TEXT);
    $stmt->bindValue(':json', $json, SQLITE3_TEXT);
    $stmt->execute();
}

// Iterate through input files
for ($i = 2; $i < $argc; $i++) {
    $inputFile = $argv[$i];

    // Check if file exists
    if (!file_exists($inputFile)) {
        echo "File $inputFile does not exist.\n";
        continue;
    }

    $fileContent = file_get_contents($inputFile);
    $lines = explode("\n", $fileContent);
    $fileContent = null;

    $outputDb->exec('BEGIN');
    foreach ($lines as $line) {
        $log = json_decode($line, true);

        // Check if JSON is valid
        if ($log === null) {
            echo "Invalid JSON: $line\n";
            continue;
        }

        $time = $log['time'];
        $json = $line;

        insertLog($outputDb, $time, $json);
    }
    $outputDb->exec('COMMIT');

}

// Sort logs by time
$result = $outputDb->query('SELECT * FROM logs ORDER BY time');

// Write sorted logs to output file
$outputFile = $argv[1].'.log';
$handle = fopen($outputFile, 'w');
if ($handle) {
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        fwrite($handle, $row['log_json'].PHP_EOL);
    }
    fclose($handle);
    echo "Merged logs written to $outputFile\n";
} else {
    echo "Error writing to file: $outputFile\n";
}

// Close database connection
$outputDb->close();
@unlink($argv[1]);
