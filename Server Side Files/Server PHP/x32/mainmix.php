<?php
include "../config.php";
session_start();



// Check permissions
if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 1)) { showAccessDenied(); exit; }

// ===== CONFIG =====
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 1 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($x32);
if (!$stmt->fetch()) { echo("No Device Found"); exit; }
$stmt->close();
if ($x32 == null){ echo "No Ip Provided!"; exit; }

$port = 10023;

// ===== OSC CORE =====
function osc($p,$t=null,$v=null){
    $pad=function($s){$l=strlen($s)+1;return $s."\0".str_repeat("\0",(4-($l%4))%4);};
    $m=$pad($p);
    if($t){
        $m.=$pad(",".$t);
        if($t==="f") $m.=pack("G",$v);
        if($t==="s") $m.=$pad($v);
        if($t==="i") $m.=pack("N",$v);
    }
    return $m;
}
$sock = stream_socket_client("udp://$x32:$port");
stream_set_blocking($sock,false);
fwrite($sock,osc("/xremote"));

function send_recv($m){
    global $sock; fwrite($sock,$m);
    $t=microtime(true);
    while(microtime(true)-$t<0.05){
        if($r=fread($sock,2048)) return $r;
    }
    return null;
}

// ===== FADER HELPERS =====
function faderToDb($f){
    if($f <= 0.0) return -90.0;
    elseif($f <= 0.0625) return -90 + ($f/0.0625)*30;
    elseif($f <= 0.25)   return -60 + (($f-0.0625)/(0.25-0.0625))*30;
    elseif($f <= 0.5)    return -30 + (($f-0.25)/(0.5-0.25))*20;
    else                  return -10 + (($f-0.5)/(1.0-0.5))*20;
}

function dbToFader($d){
    if($d <= -60) return max(0, ($d + 90)/30 * 0.0625);
    elseif($d <= -30) return 0.0625 + (($d+60)/30)*(0.25-0.0625);
    elseif($d <= -10) return 0.25 + (($d+30)/20)*(0.5-0.25);
    elseif($d <= 10) return 0.5 + (($d+10)/20)*(1.0-0.5);
    else return 1.0;
}

// ===== MUTE HELPERS =====
function setMute($ch, $mute) {
    send_recv(osc(sprintf("/ch/%02d/mix/on", $ch), "i", $mute ? 0 : 1));
}

function getMute($ch) {
    $r = send_recv(osc(sprintf("/ch/%02d/mix/on", $ch)));
    if (!$r) return null;
    $typePos = strpos($r, ",i");
    if ($typePos === false) return null;
    $intPos = ceil(($typePos + 3)/4)*4;
    $data = substr($r, $intPos, 4);
    if(strlen($data) < 4) return null;
    $val = unpack("N", $data)[1];
    return $val === 0 ? true : false;
}

// ===== FADER FUNCTIONS =====
function setFader($ch, $d){
    send_recv(osc(sprintf("/ch/%02d/mix/fader",$ch),"f",$d));
}
function getFader($ch){
    $r = send_recv(osc(sprintf("/ch/%02d/mix/fader",$ch)));
    if(!$r) return null;
    $typePos = strpos($r, ",f");
    if($typePos === false) return null;
    $floatPos = ceil(($typePos + 3)/4)*4;
    $data = substr($r, $floatPos, 4);
    if(strlen($data) < 4) return null;
    return unpack("G", $data)[1];
}

// ===== NAME FUNCTIONS =====
function getName($ch){
    $r=send_recv(osc(sprintf("/ch/%02d/config/name",$ch)));
    if(!$r) return "";
    $o=(int)(ceil((strlen("/ch/".sprintf("%02d",$ch)."/config/name")+1)/4)*4+4);
    return trim(strtok(substr($r,$o),"\0"));
}
function setName($ch,$n){
    send_recv(osc(sprintf("/ch/%02d/config/name",$ch),"s",substr($n,0,12)));
}

// ===== AJAX HANDLER =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ajax"])) {
    $ch = (int)$_POST["ch"];
    if (isset($_POST["gain"])) {
        $fader = dbToFader(floatval($_POST["gain"]));
        setFader($ch,$fader);
    }
    if (isset($_POST["mute"])) {
        $mute = $_POST["mute"] === "1" || $_POST["mute"] === "true";
        setMute($ch, $mute);
    }
    if (isset($_POST["name"])) {
        setName($ch,$_POST["name"]);
    }
    exit("OK");
}

// ===== AJAX FETCH =====
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["fetch"])) {
    $chs_data = [];
    for($i=1;$i<=32;$i++){
        $fader = getFader($i);
        $gainDb = $fader !== null ? round(faderToDb($fader),1) : null;
        $chs_data[$i] = [
            "name" => getName($i),
            "gain" => $gainDb,
            "min" => -90,
            "max" => 10,
            "mute" => getMute($i)
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($chs_data);
    exit;
}

// ===== LOAD CHANNELS =====
$chs = [];
for($i=1;$i<=32;$i++){
    $fader = getFader($i);
    $gainDb = $fader !== null ? round(faderToDb($fader),1) : null;
    $chs[$i] = [
        "name" => getName($i),
        "gain" => $gainDb,
        "min" => -90,
        "max" => 10,
        "mute" => getMute($i)
    ];
}
?>
<?php include "../header.php"; ?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<title>X32 Main Mix</title>
<style>
body{background:#1e1e1e;color:#eee;font-family:Arial}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,90px));gap:12px}
.card{background:#2b2b2b;border:1px solid #444;border-radius:8px;padding:8px;text-align:center}
.ch{font-weight:bold; height:40px;


}
input[type=text]{width:90%; color:black; margin:6px 0}
input[type=range]{writing-mode:bt-lr;-webkit-appearance:slider-vertical;height:160px; }
input[type=number]{width:90%; color:black; margin:6px 0}
.db{font-size:12px;margin-top:4px}
button{margin-top:6px;width:80%;cursor:pointer;}
.container{width:90%;}
</style>
</head>
<body>
<div class="container">
<h2>X32 Main Mix</h2>
<div class="grid">
<?php foreach($chs as $c=>$v): ?>
<div class="card">
    <div class="ch"><?=$v['name']?></div>
    
    <input type="range"
           min="<?=$v['min']?>" max="<?=$v['max']?>" step="0.5"
           value="<?=$v['gain']?>"
           oninput="sliderChanged(this,<?=$c?>)">
    <input type="number"
           min="<?=$v['min']?>" max="<?=$v['max']?>" step="0.5"
           value="<?=$v['gain']?>"
           oninput="numberChanged(this,<?=$c?>)">
    <div class="db"><?=$v['gain']?> dB</div>
</div>
<?php endforeach ?>
</div>
</div>

<script>
let timers = {};

// ===== AJAX POST =====
function post(data){
    return fetch(location.href,{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:new URLSearchParams(data)
    });
}

// ===== FADER HANDLERS =====
function sliderChanged(slider,ch){
    let card = slider.closest(".card");
    let numberInput = card.querySelector("input[type=number]");
    let dbDiv = card.querySelector(".db");

    numberInput.value = slider.value;
    dbDiv.innerText = slider.value + " dB";

    clearTimeout(timers[ch]);
    timers[ch] = setTimeout(()=>{ post({ajax:1,ch:ch,gain:slider.value}); },80);
}

function numberChanged(input,ch){
    let card = input.closest(".card");
    let slider = card.querySelector("input[type=range]");
    let dbDiv = card.querySelector(".db");

    let val = parseFloat(input.value);
    if(val < parseFloat(input.min)) val = parseFloat(input.min);
    if(val > parseFloat(input.max)) val = parseFloat(input.max);

    slider.value = val;
    dbDiv.innerText = val + " dB";

    clearTimeout(timers[ch]);
    timers[ch] = setTimeout(()=>{ post({ajax:1,ch:ch,gain:val}); },80);
}

// ===== NAME HANDLER =====
function sendName(ch,name){ post({ajax:1,ch:ch,name:name}); }

// ===== MUTE HANDLER =====
// ===== MUTE HANDLER =====
function toggleMute(ch, btn){
    let muted = btn.dataset.muted === "true"; // current state
    let newMute = !muted; // toggle

    post({ajax:1,ch:ch,mute:newMute?1:0});

    // Update button color and text
    btn.dataset.muted = newMute ? "true" : "false";
    btn.innerText = newMute ? "Unmute" : "Mute";
    btn.style.backgroundColor = newMute ? "red" : "";
}

// ===== ADD MUTE BUTTONS =====
document.querySelectorAll(".card").forEach((card,index)=>{
    let ch = index+1;
    let btn = document.createElement("button");
    btn.dataset.muted = "false"; // default state
    btn.innerText = "Mute";
    btn.onclick = ()=>toggleMute(ch,btn);
    card.appendChild(btn);
});

// ===== AUTO-REFRESH =====
setInterval(async ()=>{
    try{
        let resp = await fetch(location.href+"&fetch=1");
        if(!resp.ok) return;
        let data = await resp.json();

        for(let ch in data){
            let card = document.querySelectorAll(".card")[ch-1];
            if(!card) continue;

            let slider = card.querySelector("input[type=range]");
            let numberInput = card.querySelector("input[type=number]");
            let dbDiv = card.querySelector(".db");
            let nameInput = card.querySelector("input[type=text]");
            let muteBtn = card.querySelector("button");

            let val = data[ch].gain;
            let name = data[ch].name;
            let muted = data[ch].mute;

            if(document.activeElement!==slider && document.activeElement!==numberInput){
                slider.value = val;
                numberInput.value = val;
                dbDiv.innerText = val + " dB";
            }
            

            // Update mute button color and text
            if(muteBtn && document.activeElement!==muteBtn){
                muteBtn.dataset.muted = muted ? "true" : "false";
                muteBtn.innerText = muted ? "Mute" : "Mute";
                muteBtn.style.backgroundColor = muted ? "red" : "";
                muteBtn.style.color = muted ? "white" : "black";
            }
        }
    }catch(e){ console.error("Refresh failed",e); }
},500);


</script>
</body>
</html>
