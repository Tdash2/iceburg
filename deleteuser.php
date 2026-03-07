

<?php
include "config.php";
session_start();
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 5)) { // 2 = required permission level
    showAccessDenied();
    exit;
}

// Get the ID
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<script>alert('No color code specified.'); window.location='viewcolorcodes.php';</script>";
    exit;
}


// Delete from database
$stmt = $conn->prepare("DELETE FROM `Admin Users` WHERE `id` = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

echo "<script> window.location='viewusers.php';</script>";
?>
