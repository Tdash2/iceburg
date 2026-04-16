<?php
require "../config.php";

if (!isset($_GET['id'])) {
    die("Missing device ID");
}
session_start();
if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 3, 4)) { showAccessDenied(); exit; }

$deviceId = intval($_GET['id']);

/* ===============================
   GET DEVICE INFO
================================ */
$stmt = $conn->prepare("SELECT name, pluginID FROM devices WHERE id = ?");
$stmt->bind_param("i", $deviceId);
$stmt->execute();
$res = $stmt->get_result();
$device = $res->fetch_assoc();

$deviceName   = $device['name'] ?? "Unknown Device";
$devicePlugin = $device['pluginID'] ?? "";

/* ===============================
   MANUAL SAVE (FALLBACK)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_POST['input_umd'])) {
        foreach ($_POST['input_umd'] as $ch => $umd) {

            $stmt = $conn->prepare("
                UPDATE tally_channels
                SET umd = ?
                WHERE device_id = ? 
                AND type = 'input' 
                AND channel = ?
            ");

            $stmt->bind_param("sii", $umd, $deviceId, $ch);
            $stmt->execute();
        }
    }

    if ($devicePlugin == "4" && !empty($_POST['output_umd'])) {
        foreach ($_POST['output_umd'] as $ch => $umd) {

            $stmt = $conn->prepare("
                UPDATE tally_channels
                SET umd = ?
                WHERE device_id = ? 
                AND type = 'output' 
                AND channel = ?
            ");

            $stmt->bind_param("sii", $umd, $deviceId, $ch);
            $stmt->execute();
        }
    }

    header("Location: /tally/device.php");
    exit;
}

/* ===============================
   LOAD CURRENT CHANNELS
================================ */
$inputs  = [];
$outputs = [];

$stmt = $conn->prepare("
    SELECT type, channel, umd, name
    FROM tally_channels
    WHERE device_id = ?
    ORDER BY type, channel
");

$stmt->bind_param("i", $deviceId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {

    $entry = [
        "umd"  => $row['umd'],
        "name" => $row['name'] ?? ""
    ];

    if ($row['type'] === 'input' && $row['channel'] >= 9) {
        $inputs[$row['channel']] = $entry;
    }

    if ($row['type'] === 'output' && $row['channel'] >= 9) {
        $outputs[$row['channel']] = $entry;
    }
}

include "../header.php";
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit UMD</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body style="background:#232323;color:#fff;">
<div class="container">

<h3>
Edit UMD:
<?= htmlspecialchars($deviceName) ?>
(ID <?= $deviceId ?>)
</h3>
<a class="btn btn-primary" style="color:#FFF;"
href="/tally/device.php">
Back
</a>
<form method="post">

<div class="row">

<?php if(empty($inputs) && empty($outputs)) { ?>

<div class="col-md-12">
<div class="alert alert-warning">
No UMD are assigned to this device.
</div>
</div>

<?php } ?>

<div class="col-md-6">
<div id="inputs"></div>
</div>

<?php if($devicePlugin == "4"){ ?>
<div class="col-md-6">
<div id="outputs"></div>
</div>
<?php } ?>

</div>





</form>
</div>

<script>
const deviceId = <?= $deviceId ?>;

const existingInputs  = <?= json_encode($inputs) ?>;
const existingOutputs = <?= json_encode($outputs) ?>;

/* ---------------------------
   Build Fields
---------------------------- */
function buildFields(data, containerId, prefix) {

    const container = document.getElementById(containerId);
    container.innerHTML = '';

    Object.keys(data).forEach(ch => {

        const div = document.createElement("div");
        div.className = "form-group";

        const name = data[ch].name && data[ch].name !== ""
            ? data[ch].name
            : `${prefix} ${ch}`;

        const value = data[ch].umd ?? "";

        div.innerHTML = `
            <label>${name} UMD</label>
            <input type="text"
                   class="form-control umd-input"
                   data-type="${prefix.toLowerCase()}"
                   data-channel="${ch}"
                   value="${value}">
        `;

        container.appendChild(div);
    });
}

/* ---------------------------
   Render Fields
---------------------------- */
buildFields(existingInputs, "inputs", "Input");

<?php if($devicePlugin == "4"){ ?>
buildFields(existingOutputs, "outputs", "Output");
<?php } ?>


/* ---------------------------
   Auto Save (Debounced)
---------------------------- */
let saveTimer;

document.addEventListener("input", function(e){

    if(!e.target.classList.contains("umd-input")) return;

    clearTimeout(saveTimer);

    saveTimer = setTimeout(() => {

        fetch(window.location.href, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                device_id: deviceId,
                [e.target.dataset.type + "_umd[" + e.target.dataset.channel + "]"]:
                e.target.value
            })
        });

    }, 400);

});

</script>

</body>
</html>