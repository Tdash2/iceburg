<?php
include "../config.php";
$idd= $_GET['id'];
$stmt = $conn->prepare("SELECT ip, username, password,name FROM `devices` WHERE pluginID = 14 AND id=?");
$stmt->bind_param("i", $idd);
$stmt->execute();
$stmt->bind_result($TARGET,$username,$password,$namee);
if (!$stmt->fetch()) {
    //echo("No Device Found");
    exit;
}
$stmt->close();


$url = "http://".$TARGET."/getbuttons/";

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
$buttonCount = count($data);

echo "Found " . $buttonCount. " Buttons";

