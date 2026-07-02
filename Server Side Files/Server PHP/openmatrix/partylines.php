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





$TARGET = $TARGE . ":7000";

if (isset($_GET['proxy'])) {

    $path = $_GET['proxy'];
    $url = $TARGET . $path;

    // Preserve query parameters except "proxy"
    $query = $_GET;
    unset($query['proxy']);

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

    // Forward request body (JSON, forms, etc.)
    $body = file_get_contents('php://input');
    if ($body !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    // Forward request headers
    $headers = [];

    foreach (getallheaders() as $name => $value) {

        $lower = strtolower($name);

        // Don't forward these
        if (in_array($lower, [
            'host',
            'content-length'
        ])) {
            continue;
        }

        $headers[] = "$name: $value";
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);

    if ($response === false) {
        http_response_code(500);
        die(curl_error($ch));
    }

    // Return same status code
    http_response_code(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    // Return same content type
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if ($contentType) {
        header("Content-Type: $contentType");
    }

    echo $response;

    curl_close($ch);
    exit;

}else{

?>


<head>
    <title>Intercom Matrix PartyLines</title>
    <link rel="stylesheet" href="style.css">
</head>


<!DOCTYPE html>

<html>
<meta charset="UTF-8">
<?php
include "../header.php";
?>
<body style="background: #333;color: white;">

    <div class="body2" style="background: #333;color: white;">


        <div id="busyOverlay" style="
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.4);
    z-index:9999;
    color:white;
    font-size:24px;
    font-weight:bold;
    align-items:center;
    justify-content:center;
">
            Applying Partyline Changes...
        </div>

        <h1>Partyline</h1>

        <div class="header-row">
            <div class="controls">

                <div class="left-col">
                    <label>Select Partyline</label>
                    <div class="row">
                        <select id="plSelect" onchange="onPartyLineChange()">
                            <option value="">-- Select PartyLine --</option>
                        </select>
                    </div>
                </div>

                <div class="right-col">
                    <div class="field-group">
                        <label style=" text-align: left; ">Add A New Partyline</label>
                        <div class="row">
                            <input id="newPlName">
                            <button onclick="createPartyLine()">Create</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>



        <div id="details">
            <H2>Select a Partyline</H2>
        </div>




     
    </div>
</body>
</html>

<script>
let partyLines = [];
let selected = null;

let dspInputs = [];
let dspOutputs = [];

let stateInputs = new Set();
let stateOutputs = new Set();


// ======================
// INIT
// ======================
loadPartyLines();


// ======================
// LOAD PARTYLINES
// ======================
async function loadPartyLines() {
    showBusy();
    const res = await fetch('?id=<?php echo $idd;?>&proxy=/api/partylines');
    partyLines = await res.json();

    renderList();
    hideBusy();
}

function renderList() {
    const select = document.getElementById('plSelect');

    select.innerHTML = `
        <option value="">-- Select PartyLine --</option>
    `;

    partyLines.forEach(pl => {
        const option = document.createElement('option');

        option.value = pl.id;
        option.textContent = pl.id + " - " + pl.name;

        if (selected && selected.id === pl.id)
            option.selected = true;

        select.appendChild(option);
    });
}

function showBusy(message = "Applying Party Line Changes...") {
    const overlay = document.getElementById("busyOverlay");

    overlay.innerText = message;
    overlay.style.display = "flex";
}

function hideBusy() {
    document.getElementById("busyOverlay").style.display = "none";
}

async function renamePL(id, oldName) {
    const name = prompt("Rename PartyLine:", oldName);
    showBusy();
    if (!name || name === oldName) return;

    await fetch('?id=<?php echo $idd;?>&proxy=/api/partyline/rename',
        {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                plId: id,
                name: name
            })
        });

    await loadPartyLines
    hideBusy();
}

async function deletePL(id) {
    if (!confirm("Delete this PartyLine?")) return;
    showBusy("Deleating Partyline. This may take 5 Minutes");
    await fetch('?id=<?php echo $idd;?>&proxy=/api/partyline/delete',
        {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                plId: id
            })
        });

    selected = null;

    await loadPartyLines();

    document.getElementById('details').innerHTML = '';
    hideBusy();
}


// ======================
// SELECT PARTYLINE
// ======================
async function selectPartyLine(id) {
    selected = partyLines.find(x => x.id === id);

    await loadDSP();

    renderDetails();
}


// ======================
// LOAD DSP MATRIX
// ======================
async function loadDSP() {
    showBusy("Loading Data");
    const res = await fetch('?id=<?php echo $idd;?>&proxy=/api/matrix');
    const data = await res.json();

    dspInputs = data.inputs;
    dspOutputs = data.outputs;
    hideBusy();
}


// ======================
// UI LAYOUT (SIDE BY SIDE)
// ======================
function renderDetails() {
    if (!selected) return;

    stateInputs = new Set(selected.inputs || []);
    stateOutputs = new Set(selected.outputs || []);

    document.getElementById('details').innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>${selected.id} - ${selected.name}</h2>

        <div>
            <button onclick="renamePL(${selected.id}, '${selected.name.replace(/'/g, "\\'")}')">
                Rename
            </button>

            <button onclick="deletePL(${selected.id})">
                Delete
            </button>
        </div>
    </div>

    <div style="display:flex; gap:20px;">

        <div style="flex:1; border:1px solid #ddd; padding:10px;">
            <h3>Talkers</h3>
            <div id="inputsBox"></div>
        </div>

        <div style="flex:1; border:1px solid #ddd; padding:10px;">
            <h3>Listeners</h3>
            <div id="outputsBox"></div>
        </div>

    </div>
`;

    renderDSPLists();
}


// ======================
// DSP LISTS
// ======================
function renderDSPLists() {
    const inputsBox = document.getElementById('inputsBox');
    const outputsBox = document.getElementById('outputsBox');

    inputsBox.innerHTML = '';
    outputsBox.innerHTML = '';

    // INPUTS (Talkers)
    dspInputs.forEach(i => {
        inputsBox.innerHTML += `
<label style="display:flex; align-items:center; gap:6px;    margin: 3px;">
  <input type="checkbox"
         style="margin:0;"
         onchange="toggleInput(${i.number}, this.checked)"
         ${stateInputs.has(i.number) ? "checked" : ""}>

  <span style="display:flex; align-items:center;">
    ${i.number} - ${i.name}
  </span>
</label>
        `;
    });

    // OUTPUTS (Listeners)
    dspOutputs.forEach(o => {
        outputsBox.innerHTML += `
<label style="display:flex; align-items:center; gap:6px;     margin: 3px;">
  <input type="checkbox"
         style="margin:0;"
         onchange="toggleOutput(${o.number}, this.checked)"
         ${stateOutputs.has(o.number) ? "checked" : ""}>

  <span style="display:flex; align-items:center;">
    ${o.number} - ${o.name}
  </span>
</label>
        `;
    });
}


// ======================
// LIVE INPUT TOGGLE
// ======================
async function toggleInput(input, state) {
    showBusy();
    if (!selected) return;

    if (state) {
        stateInputs.add(input);

        await fetch('?id=<?php echo $idd;?>&proxy=/api/partyline/add-input',
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    plId: selected.id,
                    input
                })
            });
        hideBusy();
    }
    else {
        showBusy();
        stateInputs.delete(input);

        await fetch('?id=<?php echo $idd;?>&proxy=/api/partyline/remove-input',
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    plId: selected.id,
                    input
                })
            });
        hideBusy();
    }
}

async function createPartyLine() {
    showBusy();
    const name = document.getElementById('newPlName').value;

    await fetch('?id=<?php echo $idd;?>&proxy=/api/partyline', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name })
    });

    document.getElementById('newPlName').value = '';

    await loadPartyLines();
    hideBusy();
}

async function onPartyLineChange() {
    const id = parseInt(document.getElementById('plSelect').value);

    if (!id) {
        selected = null;
        document.getElementById('details').innerHTML = '';
        return;
    }

    await selectPartyLine(id);
}

// ======================
// LIVE OUTPUT TOGGLE
// ======================
async function toggleOutput(output, state) {
    if (!selected) return;
    showBusy();
    if (state) {
        stateOutputs.add(output);

        await fetch('?id=<?php echo $idd;?>&proxy=/api/partyline/add-output',
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    plId: selected.id,
                    output
                })
            });
    }
    else {
        stateOutputs.delete(output);

        await fetch('?id=<?php echo $idd;?>&proxy=/api/partyline/remove-output',
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    plId: selected.id,
                    output
                })
            });
    }
    hideBusy();
}
</script>
<?php
}
?>
