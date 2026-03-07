<?php
require "../config.php";

if (!isset($_GET['id'])) {
    die("Missing device ID");
}
session_start();
if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 2, 4)) { showAccessDenied(); exit; }
$deviceId = intval($_GET['id']);

/* ===============================
   GET DEVICE NAME
================================ */
$stmt = $conn->prepare("SELECT name ,pluginID  FROM devices WHERE id = ?");
$stmt->bind_param("i", $deviceId);
$stmt->execute();
$res = $stmt->get_result();
$device = $res->fetch_assoc();
$deviceName = $device['name'] ?? "Unknown Device";
$deviceplugin = $device['pluginID'] ?? "";


/* ===============================
   SAVE CHANGES
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===============================
       LOAD OLD CHANNEL DATA
    =============================== */

    $oldChannels = [];

    $stmt = $conn->prepare("
        SELECT type, channel, umd, state
        FROM tally_channels
        WHERE device_id = ?
    ");
    $stmt->bind_param("i", $deviceId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $oldChannels[$row['type']][$row['channel']] = [
            "umd"   => $row['umd'],
            "state" => $row['state']
        ];
    }

    /* ===============================
       REMOVE OLD CHANNELS
    =============================== */

    $stmt = $conn->prepare("DELETE FROM tally_channels WHERE device_id = ?");
    $stmt->bind_param("i", $deviceId);
    $stmt->execute();

    /* ===============================
       INSERT INPUTS
    =============================== */

    if (!empty($_POST['input_names'])) {
        foreach ($_POST['input_names'] as $ch => $name) {

            $umd   = $oldChannels['input'][$ch]['umd']   ?? "";
            $state = $oldChannels['input'][$ch]['state'] ?? 0;

            $stmt = $conn->prepare("
                INSERT INTO tally_channels 
                (device_id, type, channel, name, umd, state)
                VALUES (?, 'input', ?, ?, ?, ?)
            ");

            $stmt->bind_param("iissi", $deviceId, $ch, $name, $umd, $state);
            $stmt->execute();
        }
    }

    /* ===============================
       INSERT OUTPUTS
    =============================== */

    if (!empty($_POST['output_names'])) {
        foreach ($_POST['output_names'] as $ch => $name) {

            $umd   = $oldChannels['output'][$ch]['umd']   ?? "";
            $state = $oldChannels['output'][$ch]['state'] ?? 0;

            $stmt = $conn->prepare("
                INSERT INTO tally_channels 
                (device_id, type, channel, name, umd, state)
                VALUES (?, 'output', ?, ?, ?, ?)
            ");

            $stmt->bind_param("iissi", $deviceId, $ch, $name, $umd, $state);
            $stmt->execute();
        }
    }

    header("Location: /tally/device.php");
    exit;
}
}

/* ===============================
   LOAD CURRENT CHANNELS
================================ */
$inputs  = [];
$outputs = [];

$stmt = $conn->prepare("
    SELECT type, channel, name
    FROM tally_channels
    WHERE device_id = ?
    ORDER BY type, channel
");
$stmt->bind_param("i", $deviceId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    if ($row['type'] === 'input') {
        $inputs[$row['channel']] = $row['name'];
    } else {
        $outputs[$row['channel']] = $row['name'];
    }
}

$numInputs  = count($inputs);
$numOutputs = count($outputs);
?>
<?php include "../header.php"; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Tally Channels</title>
</head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<body style="background-color: #232323; color:#fff;">
<div class="container">
<h3>Edit Tally Channels: <?= htmlspecialchars($deviceName) ?> (ID <?= $deviceId ?>)</h3>

<form method="post">
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-6">
            <label>Number of Inputs</label>
            <input type="number" id="num_inputs" class="form-control"
                   value="<?= $numInputs ?>" min="0" max="64">
        </div>
        <?php if($deviceplugin == "4"){ ?>
        <div class="col-md-6">
            <label>Number of Outputs</label>
            <input type="number" id="num_outputs" class="form-control"
                   value="<?php echo $numOutputs; ?>" min="0" max="64">
        </div>
        <?php } ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div id="inputs"></div>
        </div>
      
        <?php if($deviceplugin == "4"){ ?>
        <div class="col-md-6">
            <div id="outputs"></div>
        </div>
        <?php } ?>
    </div>

    <button class="btn btn-primary" style="margin-top: 20px;">Save Channels</button>
    <a class="btn btn-primary" style="color: #FFF; margin-top: 20px;" href="/tally/device.php">Back</a>
</form>
</div>

<script>
const existingInputs  = <?= json_encode($inputs) ?>;
const existingOutputs = <?= json_encode($outputs) ?>;

const numInputs  = document.getElementById('num_inputs');
const numOutputs = document.getElementById('num_outputs');

function buildFields(count, containerId, prefix, existing) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';

    for (let i = 1; i <= count; i++) {
        const value = existing[i] ?? `${prefix} ${i}`;
        const div = document.createElement('div');
        div.className = 'form-group';
        div.innerHTML = `
            <label>${prefix} ${i} UMD</label>
            <input type="text" class="form-control"
                   name="${prefix.toLowerCase()}_names[${i}]"
                   value="${value}">
        `;
        container.appendChild(div);
    }
}

// Initial render
buildFields(numInputs.value,  'inputs',  'Input',  existingInputs);
<?php if($deviceplugin == "4"){ ?>
buildFields(numOutputs.value, 'outputs', 'Output', existingOutputs);
<?php } ?>
// Live updates
numInputs.onchange  = () => buildFields(numInputs.value,  'inputs',  'Input',  existingInputs);
<?php if($deviceplugin == "4"){ ?>
numOutputs.onchange = () => buildFields(numOutputs.value, 'outputs', 'Output', existingOutputs);
<?php } ?>
</script>

</body>
</html>
