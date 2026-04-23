<?php
include "../config.php";
session_start();



if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 2, $_GET['to_device'])) { showAccessDenied(); exit; }


if (!isset($_GET['from_device'], $_GET['from_ch'], $_GET['to_device'], $_GET['to_ch'])) {
    echo json_encode(['error'=>'Missing parameters']);
    exit;
}

$fromDev = intval($_GET['from_device']);
$fromCh  = intval($_GET['from_ch']);
$toDev   = intval($_GET['to_device']);
$toCh    = intval($_GET['to_ch']);

$stmt = $conn->prepare("DELETE FROM tally_mappings WHERE from_device=? AND from_channel=? AND to_device=? AND to_channel=?");
$stmt->bind_param("iiii", $fromDev, $fromCh, $toDev, $toCh);
$stmt->execute();

echo json_encode(['success'=>true]);
