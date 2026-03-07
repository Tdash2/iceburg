<?php
include "../config.php";
session_start();


}
// Check permissions
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 1, 1)) {
    showAccessDenied();
    exit;
}


?>
<?php
// CONFIG

$VIDEHub_HOST = "";

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 1 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($x32);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();


if ($x32 == null){
echo "No Ip Provided!";
exit;
}


$port = 10023;

/* ---------------- OSC helper ----------------- */
function osc($path, $type=null, $value=null){
    $pad = fn($s) => $s . str_repeat("\0", 4 - (strlen($s) % 4));
    $msg = $pad($path);
    if ($type){
        $msg .= $pad("," . $type);
        if ($type === "i") $msg .= pack("N", $value);
    }
    return $msg;
}

/* -------- shared UDP socket ------------ */
$sock = stream_socket_client("udp://$x32:$port", $errno, $errstr);
stream_set_timeout($sock, 0);
stream_set_blocking($sock, false);

function send_recv($msg){
    global $sock;
    @fwrite($sock, $msg);
    $start = microtime(true);
    while (microtime(true) - $start < 0.02){
        $resp = @fread($sock, 2048);
        if ($resp) return $resp;
    }
    return null;
}

/* --------- set P16 routing --------- */
function setP16($n, $enum){
    $addr = sprintf("/outputs/p16/%02d", $n);
    send_recv(osc($addr, "i", $enum));
}
function getCurrentP16(){
    $current = [];
    for($i=1;$i<=16;$i++){
        $addr = sprintf("/outputs/p16/%02d/src", $i);
        $resp = send_recv(osc($addr));
        if (!$resp){
            $current[$i] = 0;
            continue;
        }
        $addrPad = (int)(ceil((strlen($addr)+1)/4)*4);
        $typePad = 4;
        $offset = $addrPad + $typePad;
        $data = substr($resp, $offset);
        $val = unpack("N", $data)[1];
        $current[$i] = $val;
    }
    return $current;
}

/* -------------- POST update routing -------------- */
if (!empty($_POST["p16"])) {
  if ($_SESSION['user_permissions'] > 1){
    foreach($_POST["p16"] as $out => $enumValue){
        setP16((int)$out, intval($enumValue));
        
    
  }
  header("Location: ultranetoutputs.php?id=".$id);
    exit;}else{
    echo'<div id="errorBox" style="display:none;"></div>

    <script>
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
showError("No Permission");
    </script>
    ';
    }
    
    
    
}

/* -------------- Helpers for label/enums -------------- */
function enumForChannel($ch){ return 26 + ($ch-1); }
function enumForBus($b){ return 4 + ($b-1); }

/* ---------------- Get channel & bus names --------------- */
function getSources(){
    $sources = [];
    send_recv(osc("/xinfo")); usleep(20000);
    for($i=1;$i<=32;$i++){
        $path = sprintf("/ch/%02d/config/name",$i);
        $resp = send_recv(osc($path));
        $name = $resp ? strtok(substr($resp, (int)(ceil((strlen($path)+1)/4)*4 +4)), "\0") : "";
        $label = $name ? "DO: ".trim($name) : "DO: CH $i";
        $sources[] = ['label'=>$label,'enum'=>enumForChannel($i)];
    }
    for($i=1;$i<=16;$i++){
        $path = sprintf("/bus/%02d/config/name",$i);
        $resp = send_recv(osc($path));
        $name = $resp ? strtok(substr($resp, (int)(ceil((strlen($path)+1)/4)*4 +4)), "\0") : "";
        $label = $name ? "Bus: ".trim($name) : "Bus: Bus $i";
        $sources[] = ['label'=>$label,'enum'=>enumForBus($i)];
    }
    array_unshift($sources,
        ['label'=>'Silence','enum'=>0],
        ['label'=>'MAIN L','enum'=>1],
        ['label'=>'MAIN R','enum'=>2],
        ['label'=>'MC','enum'=>3]
    );
    return $sources;
}

$sources = getSources();
$currentP16 = getCurrentP16();
?>
<?php include "../header.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Ultranet Outputs</title>
<meta name="viewport" content="width=device-width, initial-scale=1">


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
    opacity: 100;
    transition: opacity 0.3s ease;
}
#errorBox.show {
    opacity: 1;
}
body { font-family: Arial, sans-serif; background: #232323; color: #eee;  }
h2 { color: #4db2ff; margin-bottom: 20px; }
table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 10px; 
    border: 4px solid #555;
}
th, td { 
    padding: 8px; 
    border: 4px solid #555; /* grid lines */
    text-align: left; 
}
input.search-box { 
    width: 100%; 
    
    box-sizing: border-box; 
    color: #000; 
}
.pending-change { 
    background: rgb(153 255 156) !important; 
    border: 1px solid #990000 !important; 
    color: #000; 
}
.suggestions { 
    position: absolute;
    top: 100%; /* right below the input */
    left: 0;
    background: #fff;
    border: 2px solid #000;
    max-height: 150px;
    overflow-y: auto;
    width: 100%; /* now matches the input width */
    z-index: 10;
    color: #000;
    box-sizing: border-box; /* include borders in width */
}
.search-box {
    width: 100%;
    box-sizing: border-box;
    color: #000;
    position: relative; /* make parent relative for absolute suggestions */
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
#routerTable {
    table-layout: fixed;
}
#routerTable td, #routerTable th {
    width: 11.11%; /* 9 columns ? 100 / 9 = 11.11% */
}

.table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th {
    /* padding: 8px; */
    /* line-height: 1.42857143; */
    /* vertical-align: top; */
    border-top: 1px solid #ddd;
    vertical-align: middle;
}
.table-striped>tbody>tr:nth-of-type(odd) {
    background-color: #232323;
}
.table>thead>tr>th {
    vertical-align: bottom;
    border-bottom: 4px solid #555;
}
.suggestion-item { padding:0px; cursor:pointer;     background: antiquewhite;}
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
.input-wrapper {
    position: relative; /* ensures absolute suggestions align to input */
}
</style>


</head>
<body>
<div class="container">
<h2>Ultranet Outputs</h2>
<p>Mixer: <strong><?php echo $x32; ?></strong></p>
<a href="" ><button id="reloadButton"  class="btn btn-info">Refresh</button></a>
<button id="takeButton" class="btn btn-success">Save Routing</button>
<table id="routerTable" class="table table-dark table-striped">
    <thead>
        <tr><th>Path</th><th>A1</th><th>A2</th><th>A3</th><th>A4</th><th>A5</th><th>A6</th><th>A7</th><th>A8</th></tr>
    </thead>
    <tbody></tbody>
</table>

</div>

<script>
let pendingRoutes = {};
let sources = <?php echo json_encode($sources); ?>;
let currentP16 = <?php echo json_encode($currentP16); ?>;

function updateTakeButton(){
    document.getElementById("takeButton").style.display = Object.keys(pendingRoutes).length>0 ? "inline-block":"none";
}

function buildTable() {
    const tb = document.querySelector("#routerTable tbody");
    tb.innerHTML = "";

    // We'll build two rows: first 8 channels (Path 1), then next 8 channels (Path 2)
    for (let rowStart = 1; rowStart <= 16; rowStart += 8) {
        const tr = document.createElement("tr");

        // Add the path name cell at the start of the row
        const pathCell = document.createElement("td");
        const pathName = (rowStart === 1) ? "Ultranet 1-8" : "Ultranet 9-16";
        pathCell.textContent = pathName;
        tr.appendChild(pathCell);

        // Add the 8 channels for this row
        for (let i = rowStart; i < rowStart + 8; i++) {
            const idisplay = i - rowStart + 1; // 1-8 per row
            const td = document.createElement("td");

            td.innerHTML = `
               
                <div class="input-wrapper">
                    <input class="search-box ${pendingRoutes[i] !== undefined ? 'pending-change' : ''}" 
                           data-output="${i}" autocomplete="off" 
                           value="${sources.find(s => s.enum === currentP16[i])?.label || ''}">
                    <div class="suggestions" style="display:none"></div>
                </div>
            `;
            tr.appendChild(td);
        }

        tb.appendChild(tr);

        // Initialize autocomplete for each input in this row
        tr.querySelectorAll(".search-box").forEach(initAutocomplete);
    }
}



function initAutocomplete(inputEl){
    const box = inputEl.parentElement;
    const sug = box.querySelector(".suggestions");
    let activeIndex = -1;

    inputEl.addEventListener("input",()=>{
        const v = inputEl.value.toLowerCase();
        const filtered = sources.map((s,i)=>({label:s.label, idx:i})).filter(x=>x.label.toLowerCase().includes(v));
        sug.innerHTML=""; activeIndex=-1;
        if(!filtered.length){sug.style.display="none"; return;}
        filtered.forEach(f=>{
            const div = document.createElement("div");
            div.className="suggestion-item"; div.textContent=f.label; div.dataset.index=f.idx;
            div.onclick=()=>{ selectItem(inputEl,f.idx); };
            sug.appendChild(div);
        });
        sug.style.display="block";
    });

    inputEl.addEventListener("keydown",(e)=>{
        const items = sug.querySelectorAll(".suggestion-item");
        if(!items.length) return;

        if(e.key==="ArrowDown"){ e.preventDefault(); activeIndex=(activeIndex+1)%items.length; updateActive(items); }
        else if(e.key==="ArrowUp"){ e.preventDefault(); activeIndex=(activeIndex-1+items.length)%items.length; updateActive(items); }
        else if(e.key==="Enter" || e.key==="Tab"){
            if(activeIndex<0) activeIndex=0;
            selectItem(inputEl, parseInt(items[activeIndex].dataset.index));
            if(e.key==="Tab"){
                e.preventDefault();
                // move focus to next output
                const nextOut = parseInt(inputEl.dataset.output)+1;
                const nextInput = document.querySelector(`input.search-box[data-output="${nextOut}"]`);
                if(nextInput) nextInput.focus();
            }
        }
    });

    inputEl.addEventListener("focus",()=>{ inputEl.dispatchEvent(new Event('input')); });
    document.addEventListener("click",(e)=>{ if(!box.contains(e.target)) sug.style.display="none"; });

    function updateActive(items){ items.forEach(x=>x.classList.remove("suggestion-active")); if(activeIndex>=0) items[activeIndex].classList.add("suggestion-active"); }
}

function selectItem(inputEl, idx){
    inputEl.value = sources[idx].label;
    pendingRoutes[parseInt(inputEl.dataset.output)] = sources[idx].enum;
    inputEl.classList.add("pending-change");
    updateTakeButton();
    const sug = inputEl.parentElement.querySelector(".suggestions");
    sug.style.display="none";
}

// Ctrl+Y applies all pending changes
window.addEventListener("keydown", function(e){
    if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==="y"){
        e.preventDefault(); e.stopImmediatePropagation();
        if(Object.keys(pendingRoutes).length>0){
            const form = document.createElement("form"); form.method="POST"; form.style.display="none";
            for(const out in pendingRoutes){
                const input = document.createElement("input"); input.name=`p16[${out}]`; input.value=pendingRoutes[out]; form.appendChild(input);
            }
            document.body.appendChild(form); form.submit();
        }
    }
}, true);

document.getElementById("takeButton").addEventListener("click",()=>{
    const form = document.createElement("form"); form.method="POST"; form.style.display="none";
    for(const out in pendingRoutes){
        const input = document.createElement("input"); input.name=`p16[${out}]`; input.value=pendingRoutes[out];
        form.appendChild(input);
    }
    document.body.appendChild(form); form.submit();
});

buildTable();
</script>
</body>
</html>
