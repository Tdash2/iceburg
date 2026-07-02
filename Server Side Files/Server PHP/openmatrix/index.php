<?php
include "../config.php";
session_start();




// Check permissions
if (!validateUserSession($conn, 0)) {
  showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, $_GET['id'])) {
    showAccessDenied();
    exit;
}
$idd= $_GET['id'];
$stmt = $conn->prepare("SELECT ip, username, password,name FROM `devices` WHERE pluginID = 15 AND id=?");
$stmt->bind_param("i", $idd);
$stmt->execute();
$stmt->bind_result($TARGE,$username,$password,$namee);
if (!$stmt->fetch()) {
    //echo("No Device Found");
    exit;
}
$stmt->close();



$TARGET = $TARGE.":7000";
// ----------------------
// PROXY EVERYTHING EXCEPT "/"
// ----------------------
if (isset($_GET['proxy'])) {

    $path = $_GET['proxy'];

    $url = $TARGET . $path;

    // preserve extra query params except proxy
    $query = $_GET;
    unset($query['proxy']);

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
    }

    $response = curl_exec($ch);

    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($contentType) {
        header("Content-Type: $contentType");
    }

    http_response_code(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    echo $response;

    curl_close($ch);
    exit;
}else{

?>





<!DOCTYPE html>

<html>
<meta charset="UTF-8">
<?php
include "../header.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Intercom Matrix Cross Points</title>
    <link rel="stylesheet" href="style.css">
</head>



<body>
   
    <div class="body2">
        <div id="busyOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9999;color:white;font-size:24px;font-weight:bold;align-items:center;justify-content:center;">
            Applying Party Line Changes...
        </div>

        <h1>Cross Points</h1>
        <div class="header-row">
            <div class="controls">

                <div class="left-col">
                    <label>Port</label>
                    <div class="row">
                        <select id="outputSelect"></select>
                       
                    </div>
                </div>
            </div>
        </div>



     

        <table>
            <thead>
                <tr>
                    <th>Input</th>
                    <th>Connected</th>
                    <th>Override</th>
                </tr>
            </thead>

            <tbody id="crosspoints"></tbody>
        </table>




    </div>
    </div>
    </div>
    </div>
</body>
</html>

<script>
let matrix = null;
let forcedMap = {};

// ======================
// INIT
// ======================
loadMatrix();


// ======================
// LOAD MATRIX (inputs + outputs + forced state)
// ======================
async function loadMatrix(selectedOutput = null) {
    showBusy("Loading Cross Points");

    const response = await fetch('?id=<?php echo $idd;?>&proxy=/api/matrix');
    matrix = await response.json();

    // Build forced lookup
    forcedMap = {};
    (matrix.forcedCrosspoints || []).forEach(cp => {
        forcedMap[`${cp.input}-${cp.output}`] = cp.state;
    });

    await populateOutputs(selectedOutput);

    hideBusy();
}


// ======================
// UI HELPERS
// ======================
function showBusy(message = "Loading...") {
    const overlay = document.getElementById("busyOverlay");
    overlay.innerText = message;
    overlay.style.display = "flex";
}

function hideBusy() {
    document.getElementById("busyOverlay").style.display = "none";
}


// ======================
// OUTPUT DROPDOWN
// ======================
async function populateOutputs(selectedOutput = null) {
    const select = document.getElementById('outputSelect');

    select.innerHTML = '';

    matrix.outputs.forEach(output => {
        const option = document.createElement('option');
        option.value = output.number;
        option.textContent = `${output.number} - ${output.name}`;
        select.appendChild(option);
    });

    // Manual output change
    select.onchange = async () => {
        showBusy("Loading Cross Points");
        await loadOutput(select.value);
        hideBusy();
    };

    if (
        selectedOutput &&
        matrix.outputs.some(o => String(o.number) === String(selectedOutput))
    ) {
        select.value = selectedOutput;
        await loadOutput(selectedOutput);
    }
    else if (matrix.outputs.length > 0) {
        select.value = matrix.outputs[0].number;
        await loadOutput(matrix.outputs[0].number);
    }
}


// ======================
// LOAD CROSSPOINTS FOR OUTPUT
// ======================
async function loadOutput(output) {
    const response = await fetch(`?id=<?php echo $idd;?>&proxy=/api/output/${output}`);
    const crosspoints = await response.json();

    renderCrosspoints(output, crosspoints);
}


// ======================
// LOOKUP FOR FORCE STATE
// ======================
function getForceState(input, output) {
    return forcedMap[`${input}-${output}`] || "Default";
}


// ======================
// RENDER TABLE
// ======================
function renderCrosspoints(output, crosspoints) {
    const body = document.getElementById('crosspoints');
    body.innerHTML = '';

    crosspoints.forEach(cp => {

        const row = document.createElement('tr');

        // ======================
        // INPUT NAME
        // ======================
        const inputCell = document.createElement('td');
        inputCell.textContent = `${cp.number} - ${cp.name}`;

        // ======================
        // STATUS CELL
        // ======================
        const statusCell = document.createElement('td');

        const forceState = getForceState(cp.number, parseInt(output));

let led = "";
let title = "";

if (forceState === "ForceOn") {
    led = '<i class="fa-solid fa-circle text-success"></i>';
    title = "Forced On";
}
else if (forceState === "ForceOff") {
    led = '<i class="fa-solid fa-circle text-danger"></i>';
    title = "Forced Off";
}
else {
    if (cp.connected) {
        led = '<i class="fa-solid fa-circle text-success"></i>';
        title = "As Defined (On)";
    } else {
        led = '<i class="fa-solid fa-circle text-secondary"></i>';
        title = "As Defined (Off)";
    }
}

        statusCell.innerHTML = `<span title="${title}" style="font-size:16px;">${led}</span>`;

        // ======================
        // OVERRIDE DROPDOWN
        // ======================
        const overrideCell = document.createElement('td');

        const select = document.createElement('select');

        [
            { value: "Default", label: "As Defined" },
            { value: "ForceOn", label: "Forced On" },
            { value: "ForceOff", label: "Forced Off" }
        ].forEach(state => {
            const option = document.createElement("option");

            option.value = state.value;
            option.textContent = state.label;

            if (forceState === state.value) {
                option.selected = true;
            }

            select.appendChild(option);
        });

        select.addEventListener("change", async () => {
            showBusy("Saving Override");

            try {

                if (select.value === "Default") {
                    await fetch("?id=<?php echo $idd;?>&proxy=/api/crosspoint/clear", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            input: cp.number,
                            output: parseInt(output)
                        })
                    });
                } else {
                    await fetch("?id=<?php echo $idd;?>&proxy=/api/crosspoint/force", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            input: cp.number,
                            output: parseInt(output),
                            state: select.value
                        })
                    });
                }

                // Reload everything while keeping the current output selected.
                // The loading overlay remains visible until the table is rebuilt.
                await loadMatrix(output);

            } catch (err) {
                console.error(err);
                alert("Failed to save override.");
                hideBusy();
            }
        });

        overrideCell.appendChild(select);

        // ======================
        // BUILD ROW
        // ======================
        row.appendChild(inputCell);
        row.appendChild(statusCell);
        row.appendChild(overrideCell);

        body.appendChild(row);
    });
}

</script>
<?php
}
?>
