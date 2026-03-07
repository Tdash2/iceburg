<?php
include "../config.php";
session_start();


// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 1, 2)) {
    showAccessDenied();
    exit;
}

$VIDEHub_HOST = "";

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 2 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();
?>
<?php
// videohub_input_labels.php
// Only allows editing of Videohub input labels


if ($VIDEHub_HOST == null){
    echo "No Ip Provided!";
    exit;
}
$VIDEHub_PORT = 9990;
$SOCKET_TIMEOUT = 2.0;

$action = $_GET['action'] ?? '';
$userperms= $_SESSION['user_permissions'];
if($action === 'api') {
    header('Content-Type: application/json; charset=utf-8');
    $op = $_POST['op'] ?? ($_GET['op'] ?? '');
    
    if($op === 'status') {
        $res = vh_get_full_status($VIDEHub_HOST, $VIDEHub_PORT, $SOCKET_TIMEOUT);
        echo json_encode($res);
        exit;
    } 
    elseif($op === 'set_label') {
    if($userperms > 2){
        $type = $_POST['type'] ?? '';
        $port = isset($_POST['port']) ? intval($_POST['port']) - 1 : null;
        $label = $_POST['label'] ?? '';
        if($type !== 'input' || $port === null) {
            http_response_code(400);
            echo json_encode(['error'=>'missing/invalid parameters']);
            exit;
        }
        $block = "INPUT LABELS:\r\n{$port} {$label}\r\n\r\n";
        $ack = vh_send_command_and_wait_ack($VIDEHub_HOST, $VIDEHub_PORT, $block, $SOCKET_TIMEOUT);
        echo json_encode(['ack'=>$ack]);
        exit;
        }
        
        echo json_encode(["error"=>"No Permission"]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['error'=>'unknown op']);
        exit;
    }
}
?>
<?php include "../header.php"; ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Videohub Input Labels</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
    font-family: Arial, sans-serif;
    background: #232323;
    color: #eee;
}
h2 { color:#4db2ff; margin-bottom:20px; }
table {
    width:100%;
    border-collapse: collapse;
    border: 4px solid #555;
    margin-top: 10px;
}
th, td {
    padding: 8px;
    border: 4px solid #555;
    text-align:left;
}
.table-striped>tbody>tr:nth-of-type(odd) {
    background-color:#232323;
}
input.search-box {
    width: 100%;
    box-sizing: border-box;
    color: #000;
    
}
.pending-change {
    background: #ff6666 !important;
    border: 1px solid #990000 !important;
    color:#000;
}
.suggestions {
    position: absolute;
    background:#fff;
    border: 2px solid #000;
    max-height:150px;
    overflow-y:auto;
    width:100%;
    z-index:10;
    color:#000;
}
.table>thead>tr>th {
    vertical-align: bottom;
    border-bottom: 4px solid #555;
}
.suggestion-item {  background: antiquewhite;cursor:pointer; }
.suggestion-item:hover, .suggestion-active { background:#0078ff; color:white; }
button#takeButton {
    padding: 6px 12px;
    font-size: 14px;
    line-height: 1.42857143;
    border-radius: 4px;
    background:#48c848;
    border:none;
    color:#fff;
    font-weight:bold;
    cursor:pointer;
    display:none;
    

}

button#reloadButton {
    padding: 6px 12px;
    font-size: 14px;
    line-height: 1.42857143;
    border-radius: 4px;
    background:#46b8da;
    border:none;
    color:#fff;
    font-weight:bold;
    cursor:pointer;
   
    

}
}
button, input, select, textarea {
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
    color: black;
}
.table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th {
    padding: 8px;
    line-height: 1.42857143;
    vertical-align: middle;
    border-top: 1px solid #ddd;
}
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

</style>
</head>
<body>
<div class="container">
<h2>Videohub Input Lables</h2>
<p>Router: <strong><?php echo $VIDEHub_HOST; ?></strong></p>
<button id="refresh" class="btn btn-info">Refresh</button>
<button id="submitChanges"  class="btn btn-success">Submit Changes</button>
<div id="errorBox" style="display:none;"></div>
<div id="outputs"></div>

<script>
let inputLabels = [];
let changedLabels = {};

async function api(op, data = {}) {
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const ip = urlParams.get('id');

    const form = new URLSearchParams();
    form.append("op", op);
    for (const k in data) form.append(k, data[k]);

    const res = await fetch(`?action=api&id=${encodeURIComponent(ip)}`, {
        method: "POST",
        body: form
    });
    return res.json();
}

function escapeHtml(s){ return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

async function refreshLabels(){
    document.getElementById('refresh').disabled = true;
    changedLabels = {};
    document.getElementById('submitChanges').style.display = 'none';
    try {
        const res = await api('status');
        if(res.error){ alert('Error: '+res.error); return; }
        inputLabels = res.input_labels || [];
        const count = res.input_count || inputLabels.length;

        let html = '<table><thead><tr><th>Input Number</th><th>Label</th></tr></thead><tbody>';
        for(let i=0;i<count;i++){
            const lab = inputLabels[i] || '';
            html += `<tr>
                        <td>${i+1}</td>
                        <td><input type="text" <?php if($userperms <= 2){ echo "readonly";} ?> value="${escapeHtml(lab)}" 
                            data-index="${i}" 
                            oninput="markChanged(this)" style="color: black;"/></td>
                     </tr>`;
        }
        html += '</tbody></table>';
        document.getElementById('outputs').innerHTML = html;
    } finally {
        document.getElementById('refresh').disabled = false;
    }
}

function markChanged(input){
    const index = parseInt(input.dataset.index);
    const original = inputLabels[index] || '';
    if(input.value !== original){
        input.style.backgroundColor = 'rgb(153 255 156)';
        changedLabels[index] = input.value;
    } else {
        input.style.backgroundColor = '';
        delete changedLabels[index];
    }

    document.getElementById('submitChanges').style.display = Object.keys(changedLabels).length ? 'inline-block' : 'none';
}

document.getElementById('submitChanges').addEventListener('click', async ()=>{
    const submitBtn = document.getElementById('submitChanges');
    submitBtn.disabled = true;

    for(const index in changedLabels){
        const label = changedLabels[index];
        const res = await api('set_label',{ type:'input', port:parseInt(index)+1, label: label });
        if(res.ack !== 'ACK'){
            showError(`Failed to set input ${parseInt(index)+1}: ${JSON.stringify(res)}`);
        }
    }

    await refreshLabels();
    submitBtn.disabled = false;
});
async function takeAllRoutes() {
    const submitBtn = document.getElementById('submitChanges');
    submitBtn.disabled = true;

    for (const index in changedLabels) {
        const label = changedLabels[index];
        const res = await api('set_label', { type: 'input', port: parseInt(index) + 1, label: label });
        if (res.ack !== 'ACK') {
            showError(`Failed to set output ${parseInt(index) + 1}: ${JSON.stringify(res)}`);
        }
    }

    await refreshLabels();
    submitBtn.disabled = false;
}
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

window.addEventListener("keydown",function(e){
    if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==="y"){ e.preventDefault(); e.stopImmediatePropagation(); if(Object.keys(changedLabels).length>0) takeAllRoutes(); }
},true);

document.getElementById('refresh').addEventListener('click', refreshLabels);
refreshLabels();
</script>
<?php
// Include your server-side functions from the original file:
//include_once 'videohub.php'; // or copy vh_get_full_status(), vh_send_command_and_wait_ack(), parse_vh_blocks() here
function vh_get_full_status($host, $port=9990, $timeout=2.0) {
    $ctx = stream_context_create();
    $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if(!$fp) {
        return ['error' => "Could not connect to {$host}:{$errstr} ({$errno})"];
    }
    stream_set_timeout($fp, (int)$timeout);
    stream_set_blocking($fp, true);

    // Read data until a blank line with no more data for a short period.
    // The server typically sends the initial dump right away then may wait.
    $raw = '';
    $start = microtime(true);
    // Read until we've seen at least the PROTOCOL PREAMBLE and DEVICE block,
    // or until timeout/short idle.
    while (true) {
        $buf = @fgets($fp);
        if ($buf === false) break;
        $raw .= $buf;
        // If we've had 0.2s of no data and we've got a protocol preamble and a device block, break
        $meta = stream_get_meta_data($fp);
        if (strpos($raw, "PROTOCOL PREAMBLE:") !== false && strpos($raw, "VIDEOHUB DEVICE:") !== false) {
            // if socket has been idle for >0.15s we assume initial dump complete
            if ($meta['timed_out'] || (microtime(true) - $start) > $timeout) break;
            // reset start timer slightly to allow more data
            $start = microtime(true);
        }
        // safety break if total time > timeout+0.5
        if ((microtime(true) - $start) > ($timeout + 0.5)) break;
    }
    fclose($fp);

    $blocks = parse_vh_blocks($raw);
    // Build friendly array:
    $res = [];
    if (!empty($blocks['INPUT LABELS'])) {
        $labels = [];
        foreach ($blocks['INPUT LABELS'] as $line) {
            if (preg_match('/^\s*(\d+)\s+(.*)$/', $line, $m)) {
                $labels[intval($m[1])] = $m[2];
            }
        }
        $res['input_labels'] = $labels;
        $res['input_count'] = count($labels);
    }
    if (!empty($blocks['OUTPUT LABELS'])) {
        $labels = [];
        foreach ($blocks['OUTPUT LABELS'] as $line) {
            if (preg_match('/^\s*(\d+)\s+(.*)$/', $line, $m)) {
                $labels[intval($m[1])] = $m[2];
            }
        }
        $res['output_labels'] = $labels;
        $res['output_count'] = count($labels);
    }
    if (!empty($blocks['VIDEO OUTPUT ROUTING'])) {
        $routing = [];
        foreach ($blocks['VIDEO OUTPUT ROUTING'] as $line) {
            if (preg_match('/^\s*(\d+)\s+(\d+)\s*$/', $line, $m)) {
                $routing[intval($m[1])] = intval($m[2]);
            }
        }
        $res['video_output_routing'] = $routing;
    }
    // Fill counts if missing by scanning device info
    if (!empty($blocks['VIDEOHUB DEVICE'])) {
        foreach ($blocks['VIDEOHUB DEVICE'] as $line) {
            if (preg_match('/Video inputs:\s*(\d+)/i', $line, $m)) $res['input_count'] = intval($m[1]);
            if (preg_match('/Video outputs:\s*(\d+)/i', $line, $m)) $res['output_count'] = intval($m[1]);
        }
    }
    // Return all blocks too for debugging
    $res['_raw_blocks'] = $blocks;
    return $res;
}

/**
 * Parse raw protocol text into blocks keyed by header.
 * Each block header is like "OUTPUT LABELS:" and block lines stored as array.
 */
function parse_vh_blocks($raw) {
    $lines = preg_split("/\r\n|\n|\r/", $raw);
    $blocks = [];
    $current = null;
    foreach ($lines as $ln) {
        $trim = rtrim($ln, "\r\n");
        if ($trim === '') {
            $current = null;
            continue;
        }
        if (preg_match('/^([A-Z0-9 _]+):\s*$/', $trim, $m)) {
            $current = trim($m[1]);
            if (!isset($blocks[$current])) $blocks[$current] = [];
            continue;
        }
        if ($current !== null) {
            $blocks[$current][] = $trim;
        } else {
            // stray lines before a header - ignore
        }
    }
    return $blocks;
}

/**
 * Send a block of protocol text to Videohub and wait for ACK or NAK (reads until ACK or NAK or timeout).
 * Returns 'ACK' or 'NAK' or error message string.
 */
function vh_send_command_and_wait_ack($host, $port, $block_text, $timeout=2.0) {
    $ctx = stream_context_create();
    $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if(!$fp) {
        return "ERROR_CONNECT: {$errstr} ({$errno})";
    }
    stream_set_timeout($fp, (int)$timeout);
    stream_set_blocking($fp, true);

    // Write block
    fwrite($fp, $block_text);

    // Read lines until we see ACK or NAK or timeout
    $response = '';
    $start = microtime(true);
    while (true) {
        $line = @fgets($fp);
        if ($line === false) break;
        $response .= $line;
        $l = trim($line);
        if ($l === 'ACK') {
            fclose($fp);
            return 'ACK';
        } elseif ($l === 'NAK') {
            fclose($fp);
            return 'NAK';
        }
        if ((microtime(true) - $start) > $timeout) break;
    }
    fclose($fp);
    return 'NO_ACK_NAK: ' . trim($response);
}


?>
