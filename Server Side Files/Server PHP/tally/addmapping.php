<?php
include "../config.php";
session_start();



if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 2, 4)) { showAccessDenied(); exit; }


$fd = $_GET['from_device'];
$fc = $_GET['from_ch'];
$td = $_GET['to_device'];
$tc = $_GET['to_ch'];

$stmt = $conn->prepare("
    INSERT INTO tally_mappings (from_device, from_channel, to_device, to_channel)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$fd, $fc, $td, $tc]);

echo json_encode(["mapping" => "added"]);
