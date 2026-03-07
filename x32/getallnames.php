<?php
include "../config.php";
session_start();


if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1)) { showAccessDenied(); exit; }

$id = $_GET['id'];

// Get device IP
$stmt = $conn->prepare("SELECT ip,name FROM `devices` WHERE pluginID = 1 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($x32,$x32name);
if (!$stmt->fetch()) { echo("No Device Found"); exit; }
$stmt->close();

if (!$x32) { echo "No IP Provided!"; exit; }

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

/* --------- get User Outputs ------------- */
function getUserOutputs(){
    $outputs = [];
    for($i=1;$i<=32;$i++){
        $addr = sprintf("/config/userrout/out/%02d", $i);
        $resp = send_recv(osc($addr));
        if (!$resp){
            $outputs[$i] = 0;
            continue;
        }
        $addrPad = (int)(ceil((strlen($addr)+1)/4)*4);
        $typePad = 4;
        $offset = $addrPad + $typePad;
        $data = substr($resp, $offset, 4);
        $val = unpack("N", $data)[1];
        $outputs[$i] = $val;
    }
    return $outputs;
}

/* --------- Map integer to friendly name ----- */
function userOutputName($val,$x32name){
    if($val == 0) return "OFF";
    if($val >= 1 && $val <= 32) return $x32name. " XLR In ".($val);
    if($val >= 33 && $val <= 80) return "AES50-A ".($val-32);
    if($val >= 81 && $val <= 128) return "AES50-B ".($val-80);
    if($val >= 129 && $val <= 160) return "Card In ".($val-128);
    if($val >= 161 && $val <= 166) return "Aux In ".($val-160);
    if($val == 167) return "TB Internal";
    if($val == 168) return "TB External";
    if($val >= 169 && $val <= 184) return "Output ".($val-168);
    if($val >= 185 && $val <= 200) return "P16 ".($val-184);
    if($val >= 201 && $val <= 206) return "AUX ".($val-200);
    if($val == 207) return "Monitor L";
    if($val == 208) return "Monitor R";
    return "Unknown";
}

/* --------- Get current P16/Ultranet routing --------- */
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
        $data = substr($resp, $offset, 4);
        $val = unpack("N", $data)[1];
        $current[$i] = $val;
    }
    return $current;
}

/* ---------------- Get channel & bus names --------------- */
function getSources(){
    $sources = [];
    send_recv(osc("/xinfo")); usleep(20000);
    for($i=1;$i<=32;$i++){
        $path = sprintf("/ch/%02d/config/name",$i);
        $resp = send_recv(osc($path));
        $name = $resp ? strtok(substr($resp, (int)(ceil((strlen($path)+1)/4)*4 +4)), "\0") : "";
        $label = $name ? "DO: ".trim($name) : "DO: CH $i";
        $sources[] = ['label'=>$label,'enum'=>26 + ($i-1)];
    }
    for($i=1;$i<=16;$i++){
        $path = sprintf("/bus/%02d/config/name",$i);
        $resp = send_recv(osc($path));
        $name = $resp ? strtok(substr($resp, (int)(ceil((strlen($path)+1)/4)*4 +4)), "\0") : "";
        $label = $name ? "DO: ".trim($name) : "Bus: Bus $i";
        $sources[] = ['label'=>$label,'enum'=>4 + ($i-1)];
    }
    array_unshift($sources,
        ['label'=>'Silence','enum'=>0],
        ['label'=>'MAIN L','enum'=>1],
        ['label'=>'MAIN R','enum'=>2],
        ['label'=>'MC','enum'=>3]
    );
    return $sources;
}

$currentUserOutputs = getUserOutputs();
$currentP16 = getCurrentP16();
$sources = getSources();

// Build lookup table: enum => label
$enumToLabel = [];
foreach($sources as $source){
    $enumToLabel[$source['enum']] = $source['label'];
}

// Combine outputs with resolved names
$outputs = [];
foreach($currentUserOutputs as $num => $val){
    $name = userOutputName($val,$x32name);

    // If the user output is a P16 (185-200), resolve actual routed channel name
    if($val >= 185 && $val <= 200){
        $p16Num = $val - 184;
        if(isset($currentP16[$p16Num])){
            $enum = $currentP16[$p16Num];
            if(isset($enumToLabel[$enum])){
                $name = $enumToLabel[$enum];
            }
        }
    }

    $outputs[] = [
        'number' => $num,
        'name' => $name,
        'value' => $val
    ];
}

echo json_encode($outputs, JSON_PRETTY_PRINT);
?>
