<?php
include "../config.php";
session_start();



// Check permissions
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 1, 4)) {
    showAccessDenied();
    exit;
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 4 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($x32);

if (!$stmt->fetch()) {
    echo "No Device Found";
    exit;
}
$stmt->close();

if ($x32 == null){
    echo "No Ip Provided!";
    exit;
}

$url = "http://".$x32."/status";

/* ---- 1 second timeout added here ---- */
$context = stream_context_create([
    'http' => [
        'timeout' => 1.0
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === FALSE) {
    echo 'Device cannot be pinged from the server. It might be reachable at: <a href="http://'.$x32.'">'.$x32.'</a>';
    exit;
} else {
    echo $response;
}
?>
