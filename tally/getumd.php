<?php 
include "../config.php";

if (!isset($_GET['id'])) {
    die(json_encode(["error" => "Missing device ID"]));
}

$deviceId = intval($_GET['id']);

/* ---- Optional IP update ---- */
if (isset($_GET['IP'])) {
    $ip = $_GET['IP'];

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $stmtIp = $conn->prepare("UPDATE devices SET ip = ? WHERE id = ?");
        $stmtIp->bind_param("si", $ip, $deviceId);
        $stmtIp->execute();
    }
}

/*
   Load channel names for inputs and outputs
*/
$sql = "
SELECT 
    channel,
    name,
    umd,
    type
FROM tally_channels
WHERE device_id = ?
ORDER BY channel ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $deviceId);
$stmt->execute();
$res = $stmt->get_result();

$inputs = [];
$outputs = [];

/* Build simple name maps */
while ($row = $res->fetch_assoc()) {

    if ($row['type'] === 'input') {
        $inputs[(string)$row['channel']] = $row['umd'];
    }

    if ($row['type'] === 'output') {
        $outputs[(string)$row['channel']] = $row['umd'];
    }
}

echo json_encode([
    "inputs" => $inputs,
    "outputs" => $outputs
]);
?>