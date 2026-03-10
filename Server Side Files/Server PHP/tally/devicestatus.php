<?php
header('Content-Type: application/json');


include "../config.php";

$deviceId = $_GET['id'] ?? null;

if (!$deviceId) {
    echo json_encode(false);
    exit;
}

$stmt = $conn->prepare("SELECT lastping FROM devices WHERE id = ?");
$stmt->bind_param("i", $deviceId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    $lastPing = strtotime($row['lastping']);
    $currentTime = time();

    if (($currentTime - $lastPing) <= 5) {

echo json_encode(true);

    } else {
echo json_encode(false);
            }

} else {
    echo json_encode(false);
}
?>