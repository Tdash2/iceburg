<?php

$url = "http://10.244.0.76:8100/state";

// Fetch JSON from the URL
$json = file_get_contents($url);

if ($json === false) {
    die("Failed to fetch JSON from $url");
}

// Decode JSON into associative array
$data = json_decode($json, true);

if ($data === null) {
    header("HTTP/1.1 504 Gateway Timeout");
    exit;
}

// Example usage: print all values
echo "Output Format: " . $data['output_mode'] . PHP_EOL;
