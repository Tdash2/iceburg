<?php
include "../config.php";
session_start();


// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, 6)) {
    showAccessDenied();
    exit;
}
$VIDEHub_HOST = "";

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 6 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();




$ip = $VIDEHub_HOST;

$port = 9992;

// open TCP connection
$socket = fsockopen($ip, $port, $errno, $errstr, 1);
if (!$socket) {
    header("HTTP/1.1 504 Gateway Timeout");
    exit;
}

// set read timeout to 1 second
stream_set_timeout($socket, 1);

$modelName = '';
$inputs = '';
$outputs = '';

while (!feof($socket)) {
    $line = trim(fgets($socket));

    $info = stream_get_meta_data($socket);
    if ($info['timed_out']) {
        break; // stop if socket read timed out
    }

    if (stripos($line, "Model:") === 0) {
        $modelName = trim(substr($line, strlen("Model:")));
    }

    if (stripos($line, "Name:") === 0) {
        $friendlyName = trim(substr($line, strlen("Name:")));
        break;
    }
}

fclose($socket);

// friendly name is just model name

echo '<!DOCTYPE html>
<html lang="en">';

echo "Model Name: $modelName  <br>";
echo "Friendly Name: $friendlyName\n";
?>
