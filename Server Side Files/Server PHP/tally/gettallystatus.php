<?php
include "../config.php";

/* ---- Optional IP update ---- */


if (!isset($_GET['id'])) {
    die(json_encode(["error" => "Missing device ID"]));
}
$deviceId = intval($_GET['id']);
if (isset($_GET['IP'])) {
    $ip = $_GET['IP'];

    // Validate IP before storing
    if (filter_var($ip, FILTER_VALIDATE_IP)) {

        // Use PHP time instead of MySQL NOW()
        $phpTime = date('Y-m-d H:i:s'); // current time according to PHP timezone

        $stmtIp = $conn->prepare("UPDATE devices SET ip = ?, lastping = ? WHERE id = ?");
        $stmtIp->bind_param("ssi", $ip, $phpTime, $deviceId);
        $stmtIp->execute();

        $changes['ip'] = $ip;
        $changes['lastping'] = $phpTime; // optional: return to client
    }
}



/*
   SINGLE QUERY:
   - Loads device inputs
   - Loads device outputs
   - Loads mapping states for outputs
*/
$sql = "
SELECT 
    c.channel,
    c.type,
    c.state,
    m.from_channel,
    m.from_device,
    m.to_channel,
    c2.state AS mapped_state
FROM tally_channels c
LEFT JOIN tally_mappings m
       ON m.to_device = c.device_id AND m.to_channel = c.channel
LEFT JOIN tally_channels c2 
       ON c2.device_id = m.from_device 
      AND c2.channel = m.from_channel 
      AND c2.type = 'input'
WHERE c.device_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $deviceId);
$stmt->execute();
$res = $stmt->get_result();

$inputs = [];
$outputs = [];

// Pre-fill outputs with false
while ($row = $res->fetch_assoc()) {

    if ($row['type'] === 'input') {
        // local inputs
        $inputs[(int)$row['channel']] = $row['state'] == 1;
    }

    if ($row['type'] === 'output') {
        $ch = (int)$row['channel'];

        // initialize output channel (only once)
        if (!isset($outputs[$ch])) {
            $outputs[$ch] = false;
        }

        // If this output is mapped and source input is active ?
        // OR logic: if any mapped input is ON, output becomes ON
        if ($row['mapped_state'] == 1) {
            $outputs[$ch] = true;
        }
    }
}

echo json_encode([
    "inputs" => $inputs,
    "outputs" => $outputs
]);
