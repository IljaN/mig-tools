#!/usr/bin/php
<?php

php_sapi_name() === 'cli' ?: exit(1);

if (count($argv) != 2 || $argv[1] == '-h' || $argv[1] == '--help') {
    echo "Usage: $argv[0] ENCRYPTED_SIZE_BYTES\n";
    echo "Calculates the unencrypted size in bytes of a file given the encrypted size (from disk) when using binary encoding\n";
    exit(1);
}

$encryptedSize = intval($argv[1]);
echo unencryptedSize($encryptedSize);
exit(0);


function unencryptedSize($encryptedSize) {
    $blockSize = 8192;
    $headerSize = 8192;
    $signatureSize = 96; // 96 bytes of signature per block

    $blockCount = intval($encryptedSize / $blockSize);
    if ($encryptedSize % $blockSize > 0) {
        $blockCount++;
    }

    // Remove header
    $unencryptedSize = $encryptedSize - $headerSize;
    // Remove signature for each block (-1 is to account for subtracted header)
    return $unencryptedSize - (($blockCount -1) * $signatureSize);
}

function test() {
    $tests = [
        [4055712, 4000000],
        [28509290, 28167018],
        [2807548, 2766524],
        [15029824, 14845568]
    ];

    foreach ($tests as $testParam) {
        $res = unencryptedSize($testParam[0]);
        if ($res !== $testParam[1]) {
            echo "Error: $res !== $testParam[1]\n";
        }
    }
}
