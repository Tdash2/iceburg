<?php
include "../config.php";
session_start();


// Check permissions
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 1, $_GET['id'])) {
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

/* ================= OSC CORE ================= */
function osc($p,$t=null,$v=null){
    $pad=function($s){$l=strlen($s)+1;return $s."\0".str_repeat("\0",(4-($l%4))%4);};
    $m=$pad($p);
    if($t){
        $m.=$pad(",".$t);
        if($t==="f") $m.=pack("G",$v);
        if($t==="s") $m.=$pad($v);
    }
    return $m;
}
$sock=stream_socket_client("udp://$x32:$port");
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

/* ================= HELPERS ================= */
function normToDb($n,$a,$b){ return round($n*($b-$a)+$a,1); }
function dbToNorm($d,$a,$b){ return ((float)$d-$a)/($b-$a); }

/* ================= SOURCE ================= */
function getSource($ch){
    $a=sprintf("/ch/%02d/config/source",$ch);
    $r=send_recv(osc($a));
    if(!$r) return null;
    $o=(int)(ceil((strlen($a)+1)/4)*4+4);
    return (int)unpack("N",substr($r,$o,4))[1];
}

/* ================= NAME ================= */
function getName($ch){
    $a=sprintf("/ch/%02d/config/name",$ch);
    $r=send_recv(osc($a));
    if(!$r) return "";
    $o=(int)(ceil((strlen($a)+1)/4)*4+4);
    return trim(strtok(substr($r,$o),"\0"));
}
function setName($ch,$n){
    send_recv(osc(sprintf("/ch/%02d/config/name",$ch),"s",substr($n,0,12)));
}

/* ================= HA ================= */
function setHA($ch,$d){
    send_recv(osc(sprintf("/headamp/%03d/gain",$ch-1),"f",dbToNorm($d,-12,60)));
}
function getHA($ch){
    $a=sprintf("/headamp/%03d/gain",$ch-1);
    $r=send_recv(osc($a)); if(!$r) return null;
    $o=(int)(ceil((strlen($a)+1)/4)*4+4);
    return normToDb(unpack("G",substr($r,$o,4))[1],-12,60);
}

/* ================= TRIM ================= */
function setTrim($ch,$d){
    send_recv(osc(sprintf("/ch/%02d/preamp/trim",$ch),"f",dbToNorm($d,-18,18)));
}
function getTrim($ch){
    $a=sprintf("/ch/%02d/preamp/trim",$ch);
    $r=send_recv(osc($a)); if(!$r) return null;
    $o=(int)(ceil((strlen($a)+1)/4)*4+4);
    return normToDb(unpack("G",substr($r,$o,4))[1],-18,18);
}

/* ================= AJAX HANDLER ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ajax"])) {
if (!validateUserSession($conn, 2, $_GET['id'])) { showAccessDenied(); exit; }
    $ch = (int)$_POST["ch"];
    $src = getSource($ch);
    $useHA = ($src !== null && $src >= 0 && $src <= 32);

    if (isset($_POST["gain"])) {
        $useHA ? setHA($ch,$_POST["gain"]) : setTrim($ch,$_POST["gain"]);
    }
    if (isset($_POST["name"])) {
        setName($ch,$_POST["name"]);
    }
    exit("OK");
}

/* ================= LOAD ================= */
$chs=[];
for($i=1;$i<=32;$i++){
    $src=getSource($i);
    $useHA=($src!==null && $src>=0 && $src<=32);
    $chs[$i]=[
        "name"=>getName($i),
        "mode"=>$useHA?"HA":"TRIM",
        "gain"=>$useHA?getHA($i):getTrim($i),
        "min"=>$useHA?-12:-18,
        "max"=>$useHA?60:18
    ];
}
?>
<?php include "../header.php"; ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>X32 Input Config</title>
<style>
body{background:#1e1e1e;color:#eee;font-family:Arial}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,90px));gap:12px}
.card{background:#2b2b2b;border:1px solid #444;border-radius:8px;padding:8px;text-align:center}
.ch{font-weight:bold; height:40px;
  display: flex;
  justify-content: center;
  align-items: center;

}
.mode{font-size:11px;color:#8cf}
input[type=text]{width:90%;    color: black;margin:6px 0 }
input[type=range]{writing-mode:bt-lr;-webkit-appearance:slider-vertical;height:160px;   }
input[type=number]{width:90%;    color: black;margin:6px 0 }
.db{font-size:12px;margin-top:4px}
    .container {
        width: 90%;
    }
}
</style>
</head>
<body>
<div class="container">
<h2>X32 Input Config</h2>

<div class="grid">
<?php foreach($chs as $c=>$v): ?>
<div class="card">
    
<div class="ch">CH <?=$c?> Name:</div>
    <!-- Name input -->
    <input type="text"
           value="<?=htmlspecialchars($v['name'])?>"
           onblur="sendName(<?=$c?>,this.value)"
           onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur();}">
<div class="ch">CH <?=$c?> Gain:</div>
    <!-- Gain input -->
    <input type="range"
           min="<?=$v['min']?>"
           max="<?=$v['max']?>"
           step="0.5"
           value="<?=$v['gain']?>"
           oninput="sliderChanged(this,<?=$c?>)"
    >
    <input type="number"
           min="<?=$v['min']?>"
           max="<?=$v['max']?>"
           step="0.5"
           value="<?=$v['gain']?>"
           oninput="numberChanged(this,<?=$c?>)"
    >

    <div class="db"><?=$v['gain']?> dB</div>
</div>

<?php endforeach ?>
</div>
</div>

<script>
let timers = {};

function post(data){
    return fetch(location.href,{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:new URLSearchParams(data)
    });
}

// Slider updates
function sliderChanged(slider,ch){
    let card = slider.closest(".card");
    let numberInput = card.querySelector("input[type=number]");
    let dbDiv = card.querySelector(".db");

    numberInput.value = slider.value;
    dbDiv.innerText = slider.value + " dB";

    clearTimeout(timers[ch]);
    timers[ch] = setTimeout(()=>{
        post({ajax:1,ch:ch,gain:slider.value});
    },80);
}

// Number input updates
function numberChanged(input,ch){
    let card = input.closest(".card");
    let slider = card.querySelector("input[type=range]");
    let dbDiv = card.querySelector(".db");

    // Clamp value within min/max
    let val = parseFloat(input.value);
    if(val < parseFloat(input.min)) val = parseFloat(input.min);
    if(val > parseFloat(input.max)) val = parseFloat(input.max);

    slider.value = val;
    dbDiv.innerText = val + " dB";

    clearTimeout(timers[ch]);
    timers[ch] = setTimeout(()=>{
        post({ajax:1,ch:ch,gain:val});
    },80);
}

</script>

</body>
</html>
