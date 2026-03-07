<?php
include "../config.php";

if (!isset($_GET['id'])) {
    die(json_encode(["error" => "Missing device ID"]));
}

$deviceId = intval($_GET['id']);
$changes = [];

/* ---- Optional IP update ---- */
if (isset($_GET['IP'])) {
    $ip = $_GET['IP'];

    // Validate IP before storing
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $stmtIp = $conn->prepare("UPDATE devices SET ip = ?, lastping = NOW() WHERE id = ?");
        $stmtIp->bind_param("si", $ip, $deviceId);
        $stmtIp->execute();
        $changes['ip'] = $ip;
       
    }
}

/* ---- Channel updates ---- */
// Gather updates dynamically from query string: any parameter starting with 'ch'
$updates = [];
foreach ($_GET as $key => $value) {
    if (preg_match('/^ch(\d+)$/', $key, $matches)) {
        $channel = intval($matches[1]);
        $updates[$channel] = intval($value);
    }
}

if (!empty($updates)) {
    $stmt = $conn->prepare(
        "UPDATE tally_channels 
         SET state = ? 
         WHERE device_id = ? AND channel = ? AND type='input'"
    );

    foreach ($updates as $ch => $val) {
        $stmt->bind_param("iii", $val, $deviceId, $ch);
        $stmt->execute();
    }

    $changes['channels'] = $updates;
}

echo json_encode([
    "status" => "updated",
    "changes" => $changes
]);
