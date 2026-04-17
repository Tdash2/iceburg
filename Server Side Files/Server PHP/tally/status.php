<?php
header('Content-Type: application/json');


include "../config.php";

$deviceId = $_GET['id'] ?? null;

$stmt = $conn->prepare("SELECT lastping, ip FROM devices WHERE id = ?");
$stmt->bind_param("i", $deviceId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    $lastPing = strtotime($row['lastping']);
    $ip = $row['ip'];
    $currentTime = time();

    if (($currentTime - $lastPing) <= 5) {
      echo 'Device Connected to server <br> Device IP: <a href="http://'.$ip.'">'.$ip.'</a>';
    } 
    else {
     header("HTTP/1.1 504 Gateway Timeout");
     echo 'Device was last seen online at '.date("Y-m-d g:i a", $lastPing).'. Last IP: <a href="http://'.$ip.'">'.$ip.'</a>'; 
      
    exit;
    }
} 
else {
    header("HTTP/1.1 504 Gateway Timeout");
    exit;
}
?>