<?php
include "../config.php";
session_start();

$videohuballowed = $_SESSION['videohub'];


// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, 2)) {
    showAccessDenied();
    exit;
}
$VIDEHub_HOST = "";

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 2 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();



$ip = $VIDEHub_HOST;

$port = 9990;

// open TCP connection
$socket = fsockopen($ip, $port, $errno, $errstr, 3);
if (!$socket) {
    
    header("HTTP/1.1 504 Gateway Timeout");
    exit;
}

$modelName = '';
$inputs = '';
$outputs = '';

// read until we get the VIDEOHUB DEVICE block
while (!feof($socket)) {
    $line = trim(fgets($socket));

    if (stripos($line, "Model name:") === 0) {
        $modelName = trim(substr($line, strlen("Model name:")));
    }

    if (stripos($line, "Video inputs:") === 0) {
        $inputs = trim(substr($line, strlen("Video inputs:")));
    }
    if (stripos($line, "Friendly name:") === 0) {
        $friendlyName = trim(substr($line, strlen("Friendly name:")));
    }


    if (stripos($line, "Video outputs:") === 0) {
        $outputs = trim(substr($line, strlen("Video outputs:")));
        break; // we have all values
    }
}

fclose($socket);

// friendly name is just model name

echo '<!DOCTYPE html>
<html lang="en">';

echo "Model Name: $modelName  <br>";
echo "Friendly Name: $friendlyName\n";
?>
