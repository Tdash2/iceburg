

<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 3)) { // 2 = required permission level
    showAccessDenied();
    exit;
}

// Get the ID


$id = $_GET['id'] ?? null;
if (!$id) {
    echo "No ID";
    exit;
}
// Delete from database
// Delete child rows
$stmt = $conn->prepare("DELETE FROM `tally_channels` WHERE `device_id` = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();
$stmt = $conn->prepare("DELETE FROM `tally_mappings` WHERE `from_device` = ? OR `to_device` = ?");
$stmt->bind_param("ii", $id, $id);
$stmt->execute();
$stmt->close();

// Delete device
$stmt = $conn->prepare("DELETE FROM `devices` WHERE `id` = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();


echo "<script> window.location='/device/';</script>";
?>
