<?php
include "../config.php";
session_start();


if (!validateUserSession($conn, 0)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 2)) { showAccessDenied(); exit; }

// -----------------------------------------------------------------------------
// ALLOWED INPUTS / OUTPUTS

// --- Get panel ID ---
$id = $_GET['id'] ?? 0;

// --- Get panel info from DB ---
$stmt = $conn->prepare("SELECT allowedusers, panleName, sorces, destnations, deviceID FROM `routerpanle` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($allowedusers, $panleName, $sorces, $destnations, $deviceID);
if (!$stmt->fetch()) { echo "No Device Found"; exit; }
$stmt->close();

// --- Determine allowed inputs & outputs ---
// If stored as JSON in DB:
// --- Determine allowed inputs & outputs ---
$ALLOWED_INPUTS  = [];
$ALLOWED_OUTPUTS = [];

if (!empty($sorces)) {
    $decoded = json_decode($sorces, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $ALLOWED_INPUTS = array_map('strval', $decoded);
    } else {
        $ALLOWED_INPUTS = array_map('strval', explode(",", $sorces));
    }
}

if (!empty($destnations)) {
    $decoded = json_decode($destnations, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $ALLOWED_OUTPUTS = array_map('strval', $decoded);
    } else {
        $ALLOWED_OUTPUTS = array_map('strval', explode(",", $destnations));
    }
}

// Ensure they are arrays just in case
$ALLOWED_INPUTS  = is_array($ALLOWED_INPUTS) ? $ALLOWED_INPUTS : [];
$ALLOWED_OUTPUTS = is_array($ALLOWED_OUTPUTS) ? $ALLOWED_OUTPUTS : [];


// --- Optional: check current user is allowed ---

$allowedUsers = [];
if ($allowedusers) {
    $decoded = json_decode($allowedusers, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $allowedUsers = $decoded;
    } else {
        $allowedUsers = array_map('trim', explode(",", $allowedusers));
    }
}
$userperms = $_SESSION['user_permissions'] ?? 0;
$currentUserID =$_SESSION['user_id'];
if ($userperms < 4) {
if (!in_array($currentUserID, $allowedUsers)) {
    echo "You are not allowed to access this panel.";
    exit;
}
}

// --- Now $ALLOWED_INPUTS and $ALLOWED_OUTPUTS are ready for routing ---



$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 2 AND id=?");
$stmt->bind_param("i", $deviceID);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);
if (!$stmt->fetch()) { echo "No Device Found"; exit; }
$stmt->close();

$VIDEHub_PORT = 9990;
$SOCKET_TIMEOUT = 2.0;
$userperms = $_SESSION['user_permissions'] ?? 0;

// -----------------------------------------------------------------------------
// AJAX API
// -----------------------------------------------------------------------------
$action = $_GET['action'] ?? '';
if ($action === "api") {
    header("Content-Type: application/json");
    $op = $_POST['op'] ?? $_GET['op'] ?? "";

    if ($op === "status") {
        echo json_encode(vh_get_full_status($VIDEHub_HOST, $VIDEHub_PORT, $SOCKET_TIMEOUT));
        exit;
    }

    // -------------------------------------------------------------------------
    // ROUTING (SECURE)
    // -------------------------------------------------------------------------
    

        $out = strval($_POST["output"] ?? '');
        $in  = strval($_POST["input"] ?? '');

        // CHECK ALLOWED OUTPUT
        if (!in_array($out, $ALLOWED_OUTPUTS)) {
            echo json_encode(["error" => "Output not allowed"]);
            exit;
        }

        // CHECK ALLOWED INPUT
        if (!in_array($in, $ALLOWED_INPUTS)) {
            echo json_encode(["error" => "Input not allowed"]);
            exit;
        }

        // Make sure the route exists in router


        // Send command
        $cmd = "VIDEO OUTPUT ROUTING:\r\n{$out} {$in}\r\n\r\n";
        $ack = vh_send_command_and_wait_ack($VIDEHub_HOST, $VIDEHub_PORT, $cmd, $SOCKET_TIMEOUT);

        echo json_encode(["ack" => $ack]);
        exit;
    

    
}

// Include header
include "../header.php";
?>
<meta name="viewport" content="width=device-width, initial-scale=1">

<h1><?= htmlspecialchars($panleName) ?></h1>
<p>Router: <strong><?= htmlspecialchars($VIDEHub_HOST) ?></strong></p>
<div id="errorBox" style="display:none;"></div>

<div id="routerContainer" class="router-container">
    <!-- Inputs -->
    <div>
        <h3>Inputs</h3>
        <div id="inputsGrid" class="grid"></div>
    </div>

    <!-- Outputs -->
    <div>
        <h3>Outputs</h3>
        <div id="outputsGrid" class="grid"></div>
    </div>
</div>

<style>
#errorBox {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #e74c3c;
    color: #fff;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    font-weight: bold;
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s ease;
}
#errorBox.show {
    opacity: 1;
}

body {
    background-color: #232323;
    color: #f0f0f0;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    text-align: center;
}
h1, h3 { color: #fff; }
p { color: #ccc; }

.router-container {
    display: flex;
    justify-content: center;
    gap: 80px;
    flex-wrap: wrap;
}

.grid {
    display: grid;
    grid-template-columns: repeat(5, 100px); /* matches button width */
    gap: 12px;
    justify-content: center;
}

.btn {
    width: 100px;              /* fixed width */
    height: 60px;              /* fixed height */
    padding: 8px;
    background-color: #2c2c2c;
    border: 2px solid #444;
    border-radius: 8px;
    cursor: pointer;
    color: #f0f0f0;
    font-weight: 500;
    transition: all 0.2s ease;

    display: flex;             /* enable flexbox */
    align-items: center;       /* vertical centering */
    justify-content: center;   /* horizontal centering */

    white-space: normal;       /* allow text wrapping */
    overflow-wrap: break-word; 
    word-break: break-word;   
    text-align: center;        /* needed for multiple lines */
    box-sizing: border-box;
}



.btn:hover { background-color: #3a3a3a; color: #f0f0f0; border-color: #888; }
.btn.selected { border-color: #1abc9c; background-color: #1a1a1a; }
.btn.routed { border-color: #f1c40f; background-color: #1a1a1a; }

#inputsGrid, #outputsGrid {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 4px;
}
</style>

<script>
// Inject allowed IO into JS
let ALLOWED_INPUTS = <?= json_encode($ALLOWED_INPUTS) ?>;
let ALLOWED_OUTPUTS = <?= json_encode($ALLOWED_OUTPUTS) ?>;

let INPUTS = [];
let OUTPUTS = [];
let inputNames = {};
let outputNames = {};
let routingData = {};
let selectedOutput = null;

function showError(message) {
    const box = document.getElementById("errorBox");
    box.innerText = message;
    box.classList.add("show");
    box.style.display = "block";

    setTimeout(() => {
        box.classList.remove("show");
        setTimeout(() => box.style.display = "none", 300); // match CSS transition
    }, 5000); // hide after 5 seconds
}

// -----------------------------------------------------------------------------
// Refresh Status
// -----------------------------------------------------------------------------
async function refreshStatus() {
    try {
        const ip = new URLSearchParams(window.location.search).get('id');
        const res = await fetch(`?action=api&id=${encodeURIComponent(ip)}`, {
            method: "POST",
            body: new URLSearchParams({ op: "status" }),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        const data = await res.json();

        inputNames  = data.input_labels;
        outputNames = data.output_labels;

        INPUTS  = Object.keys(inputNames).map(k => parseInt(k));
        OUTPUTS = Object.keys(outputNames).map(k => parseInt(k));

        routingData = data.video_output_routing;

        buildGrids();
    } catch (e) {
        console.error("Status fetch failed", e);
    }
}

// -----------------------------------------------------------------------------
// Build Input + Output Grids (filtered by allowed lists)
// -----------------------------------------------------------------------------
function buildGrids() {
    const inputsGrid  = document.getElementById("inputsGrid");
    const outputsGrid = document.getElementById("outputsGrid");

    inputsGrid.innerHTML = "";
    outputsGrid.innerHTML = "";

    // --- Inputs (only allowed) ---
    INPUTS.forEach(inp => {
        if (!ALLOWED_INPUTS.includes(String(inp))) return;

        const btn = document.createElement("div");
        btn.className = "btn";
        btn.innerText = inputNames[inp];

        if (selectedOutput !== null && routingData[selectedOutput] == inp) {
            btn.classList.add("routed");
        }

btn.onclick = () => {
    if (selectedOutput === null) {
        showError("Select an output first!");
        return;
    }
    routeInputToSelected(inp);
};


        inputsGrid.appendChild(btn);
    });

    // --- Outputs (only allowed) ---
    OUTPUTS.forEach(out => {
        if (!ALLOWED_OUTPUTS.includes(String(out))) return;

        const btn = document.createElement("div");
        btn.className = "btn";
        btn.innerText = outputNames[out];

        if (selectedOutput == out) btn.classList.add("selected");

        btn.onclick = () => {
            selectedOutput = out;
            buildGrids();
        };

        outputsGrid.appendChild(btn);
    });
}

// -----------------------------------------------------------------------------
// Route an input to selected output
// -----------------------------------------------------------------------------
async function routeInputToSelected(input) {
    if (selectedOutput === null) return;

    try {
        const ip = new URLSearchParams(window.location.search).get('id');
        const res = await fetch(`?action=api&id=${encodeURIComponent(ip)}`, {
            method: "POST",
            body: new URLSearchParams({ op: "route", output: selectedOutput, input }),
            headers: { "Content-Type": "application/x-www-form-urlencoded" }
        });

        const j = await res.json();
if (j.ack === "ACK") {
    routingData[selectedOutput] = input;
    buildGrids();
} else {
    showError("Routing failed: " + (j.error || j.ack));
}
    } catch (e) {
        console.error("Route request failed", e);
    }
}

// Load and auto-refresh
refreshStatus();
setInterval(refreshStatus, 10000);
</script>

<?php
// -----------------------------------------------------------------------------
// BACKEND FUNCTIONS
// -----------------------------------------------------------------------------
function vh_get_full_status($host, $port, $timeout) {
    $fp = @stream_socket_client("tcp://$host:$port", $e1, $e2, $timeout);
    if (!$fp) return ["error" => "connect failed"];

    stream_set_timeout($fp, $timeout);
    stream_set_blocking($fp, true);

    $raw = ""; 
    $start = microtime(true);

    while (true) {
        $line = fgets($fp);
        if ($line !== false) $raw .= $line;

        $meta = stream_get_meta_data($fp);
        if ($meta["timed_out"]) break;
        if ((microtime(true) - $start) > ($timeout + 0.2)) break;
    }

    fclose($fp);
    return parseStatus($raw);
}

function parseStatus($raw) {
    $blocks = [];
    $lines = preg_split("/\r?\n/", $raw);
    $block = "";

    foreach ($lines as $line) {
        if (preg_match("/^([A-Z0-9 _]+):$/", $line, $m)) {
            $block = $m[1];
        } elseif ($block) {
            if (trim($line) !== "") $blocks[$block][] = $line;
        }
    }

    $inputs = [];
    if (!empty($blocks["INPUT LABELS"])) {
        foreach ($blocks["INPUT LABELS"] as $ln) {
            if (preg_match("/(\d+)\s+(.*)/", $ln, $m)) {
                $inputs[$m[1]] = $m[2];
            }
        }
    }

    $outputs = [];
    if (!empty($blocks["OUTPUT LABELS"])) {
        foreach ($blocks["OUTPUT LABELS"] as $ln) {
            if (preg_match("/(\d+)\s+(.*)/", $ln, $m)) {
                $outputs[$m[1]] = $m[2];
            }
        }
    }

    $routing = [];
    if (!empty($blocks["VIDEO OUTPUT ROUTING"])) {
        foreach ($blocks["VIDEO OUTPUT ROUTING"] as $ln) {
            if (preg_match("/(\d+)\s+(\d+)/", $ln, $m)) {
                $routing[$m[1]] = $m[2];
            }
        }
    }

    return [
        "input_labels" => $inputs,
        "output_labels" => $outputs,
        "video_output_routing" => $routing
    ];
}

function vh_send_command_and_wait_ack($host,$port,$block,$timeout){
    $fp=@stream_socket_client("tcp://$host:$port",$e1,$e2,$timeout);
    if(!$fp) return "ERROR_CONNECT";
    fwrite($fp,$block);
    stream_set_timeout($fp,$timeout);
    $resp="";
    while($line=fgets($fp)){
        $resp.=$line;
        if(trim($line)==="ACK"){ fclose($fp); return "ACK"; }
        if(trim($line)==="NAK"){ fclose($fp); return "NAK"; }
    }
    fclose($fp); return "NO_ACK";
}

?>
