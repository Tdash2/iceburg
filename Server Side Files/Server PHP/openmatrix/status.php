<?php
include "../config.php";
$idd= $_GET['id'];
$stmt = $conn->prepare("SELECT ip, username, password,name FROM `devices` WHERE pluginID = 15 AND id=?");
$stmt->bind_param("i", $idd);
$stmt->execute();
$stmt->bind_result($TARGET,$username,$password,$namee);
if (!$stmt->fetch()) {
    //echo("No Device Found");
    exit;
}
$stmt->close();


$url = "http://".$TARGET.":7000/api/labels";

// Fetch JSON from the URL
$json = file_get_contents($url);

if ($json === false) {
    header("HTTP/1.1 504 Gateway Timeout");
    exit;
}
// Decode JSON into associative array
$data = json_decode($json, true);

if ($data === null) {
    header("HTTP/1.1 504 Gateway Timeout");
    exit;
}

// Count the buttons




$inputCount = isset($data['inputs']) ? count($data['inputs']) : 0;
$outputCount = isset($data['outputs']) ? count($data['outputs']) : 0;

echo "Intecom Matrix Size $inputCount x $outputCount";