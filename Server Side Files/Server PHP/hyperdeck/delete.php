<?php
include "../config.php";
session_start();




// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, $_REQUEST['id'])) {
    showAccessDenied();
    exit;
}
$VIDEHub_HOST = "";

$id= $_REQUEST['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 16 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();




$hyperdeckIP   = $VIDEHub_HOST;


if (!isset($_POST['filename'])) {
    http_response_code(400);
    exit("No filename supplied");
}

$name = basename(urldecode($_POST['filename']));

// Connect to HyperDeck FTP
$ftp = ftp_connect($hyperdeckIP);

if (!$ftp) {
    http_response_code(500);
    exit("Could not connect to HyperDeck FTP");
}

// Anonymous login
if (!ftp_login($ftp, "anonymous", "")) {
    ftp_close($ftp);
    http_response_code(500);
    exit("FTP login failed");
}

ftp_pasv($ftp, true);

$file1 = "/sd1/" . $name;
$file2 = "/sd2/" . $name;

// Check if files exist
$sd1Exists = ftp_rawlist($ftp, $file1) !== false;
$sd2Exists = ftp_rawlist($ftp, $file2) !== false;

$deleted = false;

// Delete only if the file is not on the other card
if ($sd1Exists && !$sd2Exists) {
    if (ftp_delete($ftp, $file1)) {
        echo "Deleted from SD1: $name";
        $deleted = true;
    }
}
elseif ($sd2Exists && !$sd1Exists) {
    if (ftp_delete($ftp, $file2)) {
        echo "Deleted from SD2: $name";
        $deleted = true;
    }
}
elseif ($sd1Exists && $sd2Exists) {
    echo "File exists on both cards. Nothing deleted.";
}
else {
    echo "File not found.";
}

ftp_close($ftp);

if (!$deleted && !$sd1Exists && !$sd2Exists) {
    http_response_code(404);
}

?>