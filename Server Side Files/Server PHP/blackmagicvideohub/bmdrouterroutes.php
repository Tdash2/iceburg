<?php
include "../config.php";
session_start();



// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
// Check permissions
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
////////////////////////////////////////////////////////////
// CONFIG
////////////////////////////////////////////////////////////
ini_set('display_errors',1);
error_reporting(E_ALL);


$VIDEHub_PORT = 9990;
$SOCKET_TIMEOUT = 2.0;

$action = $_GET['action'] ?? '';
$userperms= $_SESSION['user_permissions'];

if ($action === "api") {
    header("Content-Type: application/json");

    $op = $_POST['op'] ?? $_GET['op'] ?? '';

    if ($op === "status") {
        echo json_encode(vh_get_full_status($VIDEHub_HOST,$VIDEHub_PORT,$SOCKET_TIMEOUT));
        exit;
    }
if($userperms > 1){
    if ($op === "route") {
        $out = intval($_POST["output"]) - 1;
        $in  = intval($_POST["input"])  - 1;

        $cmd = "VIDEO OUTPUT ROUTING:\r\n{$out} {$in}\r\n\r\n";
        $ack = vh_send_command_and_wait_ack($VIDEHub_HOST,$VIDEHub_PORT,$cmd,$SOCKET_TIMEOUT);

        echo json_encode(["ack"=>$ack]);
        exit;
    }

    echo json_encode(["error"=>"unknown op"]);
    exit;
    }
    echo json_encode(["error"=>"No Permission"]);
    exit;
}
?>
<?php include "../header.php"; ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Videohub Router UI</title>

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
    background: rgb(153 255 156) !important;
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
.table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th {
    padding: 8px;
    line-height: 1.42857143;
    vertical-align: middle;
    border-top: 1px solid #ddd;
}
</style>
</head>
<body>
<div class="container">
<h2>Videohub Router</h2>
<p>Router: <strong><?php echo $VIDEHub_HOST; ?></strong></p>
<button id="reloadButton" onclick="loadStatus()" class="btn btn-info">Refresh</button>
<button id="takeButton" class="btn btn-success">TAKE</button>

<table id="routerTable" class="table table-striped">
    <thead>
        <tr>
            <th>Router Output</th>
            <th>Video</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
</div>

<script>
// === GLOBAL STATE ===
let pendingRoutes = {};
window.lastInputs  = [];
window.lastOutputs = [];
window.lastRouting = [];

// === API CALL ===
async function api(op, data = {}) {
    const ip = new URLSearchParams(window.location.search).get('id');
    const form = new URLSearchParams();
    form.append("op", op);
    for (const k in data) form.append(k, data[k]);
    const res = await fetch(`?action=api&id=${encodeURIComponent(ip)}`, { method:"POST", body:form });
    return res.json();
}

// === LOAD STATUS ===
async function loadStatus() {
    const res = await api("status");
    window.lastInputs  = res.input_labels  || [];
    window.lastOutputs = res.output_labels || [];
    window.lastRouting = res.video_output_routing || [];
    buildTable(res);
}

// === TAKE BUTTON ===
function updateTakeButton(){
    document.getElementById("takeButton").style.display = Object.keys(pendingRoutes).length>0?"inline-block":"none";
}

async function doRoutePending(out, inp){
    pendingRoutes[out-1] = inp-1;
    updateRowUI(out-1);
    updateTakeButton();
}

async function takeAllRoutes(){
    const changes = Object.entries(pendingRoutes);
    for(const [out, inp] of changes){
        await api("route",{output:Number(out)+1,input:Number(inp)+1});
    }
    pendingRoutes = {};
    updateTakeButton();
    loadStatus();
}

document.getElementById("takeButton").addEventListener("click", takeAllRoutes);

// === BUILD TABLE ===
function buildTable(status){
    const tb = document.querySelector("#routerTable tbody");
    tb.innerHTML = "";
    const inputs  = status.input_labels;
    const outputs = status.output_labels;
    const routing = status.video_output_routing;

    for(let o=0;o<outputs.length;o++){
        const hasPending = pendingRoutes[o]!==undefined;
        const currentInput = hasPending ? pendingRoutes[o] : (routing[o]!==undefined ? routing[o] : undefined);
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${o+1}: ${outputs[o]||((o+1))}</td>
            <td style="position:relative">
                <input class="search-box ${hasPending?"pending-change":""}" data-output="${o+1}" autocomplete="off" value="${currentInput!==undefined?inputs[currentInput]:""}">
                <div class="suggestions" style="display:none"></div>
            </td>
        `;
        tb.appendChild(tr);
        initAutocomplete(tr.querySelector(".search-box"), inputs, idx=>doRoutePending(o+1,idx+1));
    }
}

// === UPDATE ROW UI ===
function updateRowUI(outIdx){
    const inputEl = document.querySelector(`input.search-box[data-output="${outIdx+1}"]`);
    if(!inputEl) return;
    const hasPending = pendingRoutes[outIdx]!==undefined;
    if(hasPending) inputEl.classList.add("pending-change"); else inputEl.classList.remove("pending-change");
}

// === AUTOCOMPLETE ===
function initAutocomplete(inputEl, list, onSelect){
    const box = inputEl.parentElement;
    const sug = box.querySelector(".suggestions");
    let activeIndex = -1;

    function renderSuggestions(filtered){
        sug.innerHTML="";
        activeIndex=-1;
        if(!filtered.length){sug.style.display="none"; return;}
        filtered.forEach(item=>{
            const div = document.createElement("div");
            div.className="suggestion-item"; div.textContent=item.label; div.dataset.index=item.idx;
            div.onclick=()=>{ inputEl.value=item.label; sug.style.display="none"; onSelect(item.idx); };
            sug.appendChild(div);
        });
        sug.style.display="block";
    }

    inputEl.addEventListener("input",()=>{
        const v = inputEl.value.toLowerCase();
        const filtered = list.map((label, idx)=>({label, idx})).filter(x=>x.label.toLowerCase().includes(v));
        renderSuggestions(filtered);
    });

    inputEl.addEventListener("focus",()=>{ renderSuggestions(list.map((l,i)=>({label:l,idx:i}))); });

    inputEl.addEventListener("keydown",(e)=>{
        const items = sug.querySelectorAll(".suggestion-item"); if(!items.length) return;
        if(e.key==="ArrowDown"){ e.preventDefault(); activeIndex=(activeIndex+1)%items.length; updateActive(items);}
        else if(e.key==="ArrowUp"){ e.preventDefault(); activeIndex=(activeIndex-1+items.length)%items.length; updateActive(items);}
        else if(e.key==="Enter" || e.key==="Tab"){ e.preventDefault(); const idx=parseInt(items[activeIndex<0?0:activeIndex].dataset.index); inputEl.value=list[idx]; sug.style.display="none"; onSelect(idx); }
    });

    function updateActive(items){ items.forEach(x=>x.classList.remove("suggestion-active")); if(activeIndex>=0) items[activeIndex].classList.add("suggestion-active"); }

    document.addEventListener("click",e=>{ if(!box.contains(e.target)) sug.style.display="none"; });
}

// === CTRL+Y ===
window.addEventListener("keydown",function(e){
    if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==="y"){ e.preventDefault(); e.stopImmediatePropagation(); if(Object.keys(pendingRoutes).length>0) takeAllRoutes(); }
},true);

// === START ===
loadStatus();
</script>

</body>
</html>

<?php
// ================= BACKEND =================
function vh_get_full_status($host,$port,$timeout){
    $fp=@stream_socket_client("tcp://$host:$port",$e1,$e2,$timeout);
    if(!$fp) return ["error"=>"connect failed: $e2"];
    stream_set_timeout($fp,$timeout);
    stream_set_blocking($fp,true);

    $raw=""; $start=microtime(true);
    while(true){
        $line=fgets($fp);
        if($line!==false) $raw.=$line;
        $meta=stream_get_meta_data($fp);
        if($meta["timed_out"]) break;
        if((microtime(true)-$start)>($timeout+0.2)) break;
    }
    fclose($fp);
    return parseStatus($raw);
}

function parseStatus($raw){
    $blocks=[]; $lines=preg_split("/\r?\n/",$raw); $block="";
    foreach($lines as $line){
        if(preg_match("/^([A-Z0-9 _]+):$/",$line,$m)) $block=$m[1];
        elseif($block){ if(trim($line)!=="") $blocks[$block][]=$line; }
    }
    $inputs=[]; if(!empty($blocks["INPUT LABELS"])) foreach($blocks["INPUT LABELS"] as $ln) if(preg_match("/(\d+)\s+(.*)/",$ln,$m)) $inputs[$m[1]]=$m[2];
    $outputs=[]; if(!empty($blocks["OUTPUT LABELS"])) foreach($blocks["OUTPUT LABELS"] as $ln) if(preg_match("/(\d+)\s+(.*)/",$ln,$m)) $outputs[$m[1]]=$m[2];
    $routing=[]; if(!empty($blocks["VIDEO OUTPUT ROUTING"])) foreach($blocks["VIDEO OUTPUT ROUTING"] as $ln) if(preg_match("/(\d+)\s+(\d+)/",$ln,$m)) $routing[$m[1]]=$m[2];
    return ["input_labels"=>$inputs,"output_labels"=>$outputs,"video_output_routing"=>$routing];
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
