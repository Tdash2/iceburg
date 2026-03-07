<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 0)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 5)) { showAccessDenied(); exit; }



$videohubid="30";
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT ip, name FROM `devices` WHERE pluginID = 5 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST,$name2);
if (!$stmt->fetch()) { echo "No Device Found"; exit; }
$stmt->close();

$PANEL_PORT = 9991;
$SOCKET_TIMEOUT = 1.0;

// Preload all button states
$types = [];
$targets = [];
$fp = @stream_socket_client("tcp://$VIDEHub_HOST:$PANEL_PORT", $e1, $e2, $SOCKET_TIMEOUT);
if ($fp) {
    fwrite($fp, "\r\n");
    stream_set_timeout($fp, $SOCKET_TIMEOUT);
    $resp = "";
    while ($line = fgets($fp)) { 
        $resp .= $line; 
        if (feof($fp)) break; 
    }
    fclose($fp);

    if (preg_match('/BUTTON KIND:\s*(.*?)\n\n/s', $resp, $match_kind)) {
        foreach (explode("\n", trim($match_kind[1])) as $l) {
            if (preg_match('/^(\d+)\s+(Source|Destination|Salvo)$/', $l, $m)) {
                $types[intval($m[1])] = $m[2];
            }
        }
    }

    if (preg_match('/BUTTON SDI_A:\s*(.*?)\n\n/s', $resp, $match_target)) {
        foreach (explode("\n", trim($match_target[1])) as $l) {
            if (preg_match('/^(\d+)\s+(-?\d+)$/', $l, $m)) {
                $targets[intval($m[1])] = intval($m[2]);
            }
        }
    }
   
   if (preg_match('/Videohub address:\s*([\d\.]+)/i', $resp, $m)) {
    $panel_videohub_ip = $m[1];

}

}

$userperms= $_SESSION['user_permissions'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if($userperms > 1){
        
        // --- New: Handle Videohub change ---
        if(isset($_POST['vh_ip']) && filter_var($_POST['vh_ip'], FILTER_VALIDATE_IP)) {
            $new_ip = $_POST['vh_ip'];
            
            $cmd = "NETWORK:\r\n Videohub address: $new_ip\r\n\r\n";
            $ack = vh_send_command_and_wait_ack($VIDEHub_HOST, $PANEL_PORT, $cmd, $SOCKET_TIMEOUT);
            
            
            echo json_encode(['status' => 'ok', 'message' => 'Videohub connection updated', 'new_ip' => $new_ip]);
            exit;
        }

        // --- Existing button handling ---
        if(isset($_POST["button"], $_POST["type"], $_POST["target"])) {
            $button = intval($_POST["button"]);
            $type   = $_POST["type"];
            $target = intval($_POST["target"]);

            $cmd = "BUTTON KIND:\r\n$button $type\r\n\r\n";
            $ack = vh_send_command_and_wait_ack($VIDEHub_HOST, $PANEL_PORT, $cmd, $SOCKET_TIMEOUT);
            
            $cmd = "BUTTON SDI_A:\r\n$button $target\r\n\r\n";
            $ack = vh_send_command_and_wait_ack($VIDEHub_HOST, $PANEL_PORT, $cmd, $SOCKET_TIMEOUT);

            echo json_encode(['status' => 'ok', 'ack' => $ack]);
            exit;
        }

    } else {
        echo json_encode(['status' => 'reload', 'url' => "/blackmagicrouterpanel/?id=".$id]);
        exit;
    }
}

function vh_send_command_and_wait_ack($host, $port, $block, $timeout){
    $fp = @stream_socket_client("tcp://$host:$port", $e1, $e2, $timeout);
    if (!$fp) return "ERROR_CONNECT";
    fwrite($fp, $block);
    stream_set_timeout($fp, $timeout);
    $resp = "";
    while ($line = fgets($fp)) {
        $resp .= $line;
        if (trim($line) === "ACK") { fclose($fp); return "ACK"; }
        if (trim($line) === "NAK") { fclose($fp); return "NAK"; }
    }
    fclose($fp); 
    return "NO_ACK";
}
include "../header.php";
?>

<!DOCTYPE html>
<html>
<head>
<title>Videohub Smart Control Programmer</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: Arial, sans-serif; background: #232323; color: #eee; }
/* ===== Button Grid ===== */
.grid {
    display: grid;
    grid-template-columns: repeat(20, 1fr); /* 20 buttons per row */
    grid-auto-rows: 60px; /* button height */
    gap: 6px;
    width: 100%;
    max-width: 100%;
    justify-items: center;
}

/* ===== Grid Buttons ===== */
button.grid-btn {
    width: 100%;       /* fill the grid column */
    height: 100%;      /* fill the grid row */
    
    border: none;
    border-radius: 4px;
    cursor: pointer;
    
    display: flex;
    flex-direction: column; /* allow text to wrap to multiple lines */
    align-items: center;
    justify-content: center;
    
    padding: 4px;
    font-size: 12px;
    text-align: center;
    white-space: normal;      /* allow wrapping */
    word-break: break-word;   /* break long words */
}

/* ===== Button Types ===== */
.grid-btn.source { background: #7f8c8d; color:#000; }
.grid-btn.destination { background: #f1c40f; color:#fff; }
.grid-btn.salvo { background: #27ae60; color:#fff; }

/* ===== Headings ===== */
h1, h2 {
    width: 100%;
    text-align: left;
    margin: 0 0 8px 0;
    color: #4db2ff;
}

.container p {
    width: 100%;
    text-align: left;
    margin: 0 0 20px 0;
}

/* ===== Dialog / Overlay / Suggestions (unchanged) ===== */
.dialog { display:none; position:fixed; top:100px; left:50%; transform:translateX(-50%); background:#222; padding:20px; border-radius:8px; width:300px; }
.dialog input, .dialog select { width:100%; margin:5px 0; padding:8px; background:#333; color:white; border:1px solid #555; border-radius:4px; }
.dialog button { margin-top:10px; width:100%; padding:8px; }
.overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); }
.suggestions { position:absolute; background:#333; border:1px solid #555; width:100%; max-height:150px; overflow-y:auto; z-index:10; display:none; color:#fff; }
.suggestion-item { padding:5px; cursor:pointer; }
.suggestion-item:hover { background:#0078ff; }
.dialog { display:none; position:fixed; top:100px; left:50%; transform:translateX(-50%); background:#222; padding:20px; border-radius:8px; width:300px; }
.dialog input { width:100%; margin:5px 0; padding:8px; background:#333; color:white; border:1px solid #555; border-radius:4px; }
.dialog select { width:100%; margin:5px 0; padding:8px; background:#333; color:white; border:1px solid #555; border-radius:4px; }
.dialog button { margin-top:10px; color: black; }
button, input, select, textarea {
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
    color: black;
}
</style>


</head>
<body>

<div class="container">
<br>
    <h2><?php echo $name2; ?> Panel Control</h2>
    <p>Panle : <strong><?php echo $VIDEHub_HOST; ?></strong></p>
    <div class="vh-selector" style="margin-bottom:20px;">
    <label for="vh-select">Select Videohub:</label>
    <select id="vh-select">
        <?php
        $res = $conn->query("SELECT ip, name,id FROM `devices` WHERE pluginID = 2");
        while($row = $res->fetch_assoc()) {
            // Automatically select the panel's current Videohub
            
            $selected = ($row['ip'] == $panel_videohub_ip) ? "selected" : "";
             if ($row['ip'] == $panel_videohub_ip){
             $videohubid= $row['id'];
             }
            
            
            echo "<option value='{$row['ip']}' $selected>{$row['name']} ({$row['ip']})</option>";
        }
        ?>
    </select>
    <button id="set-vh">Set Videohub</button>
</div>




    <div class="grid">
        <?php for($i=0;$i<40;$i++): ?>
            <button class="grid-btn" data-button="<?= $i ?>"></button>
        <?php endfor; ?>
    </div>
</div>




<div class="overlay"></div>
<div class="dialog">
    <h3>Button <span id="dialog-button"></span></h3>
    <form id="button-form">
        <input type="hidden" name="button" id="form-button">
        <label>Type:</label>
        <select name="type" id="form-type">
            <option>Source</option>
            <option>Destination</option>

        </select>
        <label>Target Name:</label>
        <input type="text" id="form-target" autocomplete="off">
        <div class="suggestions"></div>
        <button type="submit">Apply</button>
        <button type="button" id="close-dialog">Cancel</button>
    </form>
</div>

<script>
// Preloaded button status
const buttonStatus = <?= json_encode(array_map(function($i) use ($types, $targets){
    return ['type' => $types[$i] ?? 'Source','target' => $targets[$i] ?? 0];
}, range(0,39))) ?>;

let hubNames = { inputs: [], outputs: [] };
const dialog = document.querySelector(".dialog");
const overlay = document.querySelector(".overlay");
const form = document.getElementById("button-form");
const typeSelect = document.getElementById("form-type");
const targetInput = document.getElementById("form-target");
const sugBox = document.querySelector(".suggestions");

let selectedIndex = -1; // currently highlighted suggestion

// Fetch hub names


// Update button colors and labels
// Update button colors and labels
function updateButtonAppearance() {
    document.querySelectorAll(".grid-btn").forEach(btn => {
        const i = parseInt(btn.dataset.button);
        const data = buttonStatus[i];
        const type = data.type.toLowerCase();

        btn.classList.remove("source", "destination", "salvo");
        if (type === 'source') btn.classList.add("source");
        else if (type === 'destination') btn.classList.add("destination");
        else if (type === 'salvo') btn.classList.add("salvo");

        let label = "";

        // Only set label if target is NOT -1
        if (data.target !== -1) {
            if (type === 'source' && hubNames.inputs?.[data.target] !== undefined) {
                label = hubNames.inputs[data.target];
            } else if (type === 'destination' && hubNames.outputs?.[data.target] !== undefined) {
                label = hubNames.outputs[data.target];
            } else {
                label = data.target;
            }
        }

        btn.textContent = label;
        btn.title = `Type: ${data.type}\nTarget: ${data.target}`;
    });
}

fetch("http://<?php echo $_SERVER['HTTP_HOST'];?>/blackmagicvideohub/getvidohubnames.php?id=<?php echo $videohubid; ?>")
.then(res => res.json())
.then(data => { hubNames = data; updateButtonAppearance(); })
.catch(err => { console.error(err); updateButtonAppearance(); });

// Populate suggestions based on type
function populateSuggestions(type) {
    let list = [];
    if (type === 'Source') list = hubNames.inputs;
    else if (type === 'Destination') list = hubNames.outputs;

    sugBox.innerHTML = '';

    // --- Add TEST option ---
    const testDiv = document.createElement("div");
    testDiv.className = "suggestion-item";
    testDiv.textContent = "None";
    testDiv.dataset.index = -1;
testDiv.onclick = () => {
    targetInput.value = "None";
    targetInput.dataset.index = -1;
    sugBox.style.display = 'none';
    selectedIndex = -1;
};
    sugBox.appendChild(testDiv);
    // -----------------------

    // Normal options
    list.forEach((name, idx) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = name;
        div.dataset.index = idx;
        div.onclick = () => {
            targetInput.value = name;
            targetInput.dataset.index = idx;
            sugBox.style.display = 'none';
            selectedIndex = -1;
        };
        sugBox.appendChild(div);
    });
}


// Button click opens dialog
document.querySelectorAll(".grid-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        const i = btn.dataset.button;
        document.getElementById("dialog-button").textContent = i;
        document.getElementById("form-button").value = i;

        const data = buttonStatus[i];
        const type = data.type;
        const target = data.target;

        typeSelect.value = type;

        if (target === -1) {
            targetInput.value = "None";
            targetInput.dataset.index = -1;
        } else if (type === 'Source') {
            targetInput.value = hubNames.inputs[target] ?? target;
            targetInput.dataset.index = target;
        } else if (type === 'Destination') {
            targetInput.value = hubNames.outputs[target] ?? target;
            targetInput.dataset.index = target;
        } else {
            targetInput.value = target;
            targetInput.dataset.index = target;
        }

        populateSuggestions(type);
        sugBox.style.display = 'none';
        selectedIndex = -1;

        dialog.style.display = 'block';
        overlay.style.display = 'block';
    });
});


// Type change
typeSelect.addEventListener("change",()=>{
    populateSuggestions(typeSelect.value);
    targetInput.value=''; targetInput.dataset.index='';
    sugBox.style.display='none';
    selectedIndex=-1;
});

// Input filtering and arrow key navigation
targetInput.addEventListener("input", () => {
    const val = targetInput.value.toLowerCase();
    const visibleItems = Array.from(sugBox.children).filter(item => {
        const show = item.textContent.toLowerCase().includes(val);
        item.style.display = show ? 'block' : 'none';
        return show;
    });
    selectedIndex = -1;
    sugBox.style.display = visibleItems.length ? 'block' : 'none';
});

targetInput.addEventListener("keydown", e => {
    const visibleItems = Array.from(sugBox.children).filter(item => item.style.display !== 'none');
    if (!visibleItems.length) return;

    if (e.key === "ArrowDown") {
        e.preventDefault();
        selectedIndex = (selectedIndex + 1) % visibleItems.length;
        updateHighlight(visibleItems);
    } else if (e.key === "ArrowUp") {
        e.preventDefault();
        selectedIndex = (selectedIndex - 1 + visibleItems.length) % visibleItems.length;
        updateHighlight(visibleItems);
    } else if (e.key === "Enter") {
        e.preventDefault();
        if (selectedIndex === -1) selectedIndex = 0; // Default to top option
        const item = visibleItems[selectedIndex];
        targetInput.value = item.textContent;
        targetInput.dataset.index = item.dataset.index;
        sugBox.style.display = 'none';
        selectedIndex = -1;
    }
});
const vhSelect = document.getElementById("vh-select");
const setVhBtn = document.getElementById("set-vh");

setVhBtn.addEventListener("click", () => {
    const newIp = vhSelect.value;
    const formData = new FormData();
    formData.append("vh_ip", newIp);

    fetch("", { method: "POST", body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === "ok") {
                alert("Videohub connection updated to: " + data.new_ip);
                // Optional: update the displayed panel IP
                document.querySelector(".container p strong").textContent = data.new_ip;
            } else {
                alert("Failed to set Videohub");
            }
        })
        .catch(err => console.error(err));
});



function updateHighlight(visibleItems) {
    visibleItems.forEach((item, i) => {
        item.style.background = (i === selectedIndex) ? '#0078ff' : '';
    });
}

// Close dialog
document.getElementById("close-dialog").addEventListener("click",()=>{
    dialog.style.display='none'; overlay.style.display='none';
});

// Submit
form.addEventListener("submit",e=>{
    e.preventDefault();
    dialog.style.display='none'; overlay.style.display='none';
    const formData = new FormData(form);
    const button = formData.get("button");
    const type = formData.get("type");
    const target = targetInput.dataset.index ?? targetInput.value;
    buttonStatus[button]={type,type,target:parseInt(target)};
    updateButtonAppearance();

    const submitData = new FormData();
    submitData.append("button", button);
    submitData.append("type", type);
    submitData.append("target", target);

    fetch("",{method:"POST",body:submitData})
    .then(res=>res.json())
    .then(data=>{ if(data.status==="reload") window.location.href=data.url; })
    .catch(err=>console.error(err));
});
</script>

</body>
</html>