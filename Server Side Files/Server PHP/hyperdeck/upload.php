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

if (!isset($_FILES['file'])) {
    http_response_code(400);
    exit("No file uploaded");
}

$tmp  = $_FILES['file']['tmp_name'];
$originalName = $_FILES['file']['name'];
$extension = pathinfo($originalName, PATHINFO_EXTENSION);

if (isset($_POST['filename']) && trim($_POST['filename']) !== '') {

    $newName = trim($_POST['filename']);

    // Remove extension if the user typed it
    if (strtolower(pathinfo($newName, PATHINFO_EXTENSION)) === strtolower($extension)) {
        $newName = pathinfo($newName, PATHINFO_FILENAME);
    }

    $name = basename($newName . "." . $extension);

} else {

    $name = basename($originalName);

}

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

// Try upload locations
$destinations = [
    "/sd1/" . $name,
    "/sd2/" . $name
];

$uploaded = false;

foreach ($destinations as $destination) {
    if (ftp_put($ftp, $destination, $tmp, FTP_BINARY)) {
        $uploaded = true;
        echo "Uploaded: $destination";
        break;
    }
}

ftp_close($ftp);

if (!$uploaded) {
    http_response_code(500);
    exit("FTP upload failed on both /sd1/ and /sd2/");
}

?>