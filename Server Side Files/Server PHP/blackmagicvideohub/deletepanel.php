
<?php
include "../config.php";
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
$rtrid = $_GET['rtrid'] ?? null;
if (!$id) {
    echo "<script> window.location='managevirtialpanles.php?id=".$rtrid ."';</script>";
    exit;
}


// Delete from database
$stmt = $conn->prepare("DELETE FROM `routerpanle` WHERE `id` = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

echo "<script> window.location='managevirtialpanles.php?id=".$rtrid ."';</script>";
?>
