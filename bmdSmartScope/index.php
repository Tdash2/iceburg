<?php
include "../config.php";
session_start();




// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, 6)) {
    showAccessDenied();
    exit;
}
$VIDEHub_HOST = "";

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 6 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();




$SMARTVIEW_IP  = $VIDEHub_HOST;


$PORT = 9992;
$TIMEOUT = 1.0;

/* ========= SOCKET HELPERS ========= */
function bm_send($cmd){
    global $SMARTVIEW_IP, $PORT, $TIMEOUT;

    $fp = @stream_socket_client(
        "tcp://$SMARTVIEW_IP:$PORT",
        $errno,
        $errstr,
        $TIMEOUT
    );

    if(!$fp) return false;

    stream_set_timeout($fp, $TIMEOUT);
    fwrite($fp, $cmd);

    // Short read window, but don't require response
    usleep(100000); // 100ms
    fclose($fp);
    return true;
}


function bm_read(){
    global $SMARTVIEW_IP, $PORT, $TIMEOUT;

    $fp = @stream_socket_client(
        "tcp://$SMARTVIEW_IP:$PORT",
        $errno,
        $errstr,
        $TIMEOUT
    );

    if(!$fp) return "";

    stream_set_timeout($fp, $TIMEOUT);
    fwrite($fp, "\r\n");

    $resp = "";
    $start = microtime(true);

    while (!feof($fp)) {
        $line = fgets($fp, 4096);
        if ($line !== false) {
            $resp .= $line;
        }

        $info = stream_get_meta_data($fp);
        if ($info['timed_out']) break;

        // Safety exit (SmartView keeps socket open)
        if ((microtime(true) - $start) > $TIMEOUT) break;
    }

    fclose($fp);
    return $resp;
}


/* ========= AJAX HANDLER ========= */
if($_SERVER["REQUEST_METHOD"]==="POST"){
    $mon   = $_POST['monitor'];
    $param = $_POST['param'];
    $value = $_POST['value'];

    // LF ONLY — no CR
    $cmd = "MONITOR $mon:\n$param: $value\n\n";

    $ok = bm_send($cmd);
    echo json_encode(["ok"=>$ok,"cmd"=>$cmd]);
    exit;
}


/* ========= READ DEVICE STATE ========= */
$status = bm_read();

function parseBlock($block,$text){
    if(preg_match("/$block:\s*(.*?)\n\n/s",$text,$m)){
        $out=[];
        foreach(explode("\n",trim($m[1])) as $l){
            if(strpos($l,":")!==false){
                [$k,$v]=array_map("trim",explode(":",$l,2));
                $out[$k]=$v;
            }
        }
        return $out;
    }
    return [];
}

$monA = parseBlock("MONITOR A",$status);
$monB = parseBlock("MONITOR B",$status);
include "../header.php";
?>
<!DOCTYPE html>
<html>
<head>
<title>SmartView Control</title>
<style>
body{background:#1e1e1e;color:#eee;font-family:Arial}
.panel{background:#2b2b2b;padding:15px;border-radius:8px;margin:10px;width:300px}
h1{color:#4db2ff}
label{display:block;margin-top:10px}
input,select{width:100%}
.container {
    display: block;
}

.monitors {
    display: flex;
    flex-direction: row;   /* SIDE BY SIDE */
    gap: 20px;
    margin-top: 20px;
    flex-wrap: wrap;       /* allows wrap on small screens */
}

.panel {
    background:#2b2b2b;
    padding:15px;
    border-radius:8px;
    width:300px;
}
input, select {
    width: 100%;
    color: black;
}
</style>
</head>
<body>
<div class="container">
    <h1>Blackmagic SmartView Control</h1>
    <p>Device: <strong><?= $SMARTVIEW_IP ?></strong></p>

    <div class="monitors">
        <?php foreach(["A"=>$monA,"B"=>$monB] as $id=>$m): ?>
        <div class="panel">
            <h2>Monitor <?= $id ?></h2>

            <label>Brightness</label>
            <input type="range" min="0" max="255"
                value="<?= $m['Brightness'] ?? 128 ?>"
                oninput="send('<?= $id ?>','Brightness',this.value)">

            <label>Contrast</label>
            <input type="range" min="0" max="255"
                value="<?= $m['Contrast'] ?? 128 ?>"
                oninput="send('<?= $id ?>','Contrast',this.value)">

            <label>Saturation</label>
            <input type="range" min="0" max="255"
                value="<?= $m['Saturation'] ?? 128 ?>"
                oninput="send('<?= $id ?>','Saturation',this.value)">

            <label>Scope Mode</label>
            <select onchange="send('<?= $id ?>','ScopeMode',this.value)">
                <?php
                $modes=["Picture","WaveformLuma","Vector100","ParadeRGB","ParadeYUV","Histogram","AudioDbfs","AudioDbvu"];
                $modesname=["Picture","Wave Form","Vector","Parade RGB","Parade YUV","Histogram","Audio Dbfs","Audio Dbvu"];
                foreach ($modes as $i => $mode) {
                    $label = $modesname[$i] ?? $mode;
                    $sel = (($m['ScopeMode'] ?? '') === $mode) ? "selected" : "";
                    echo "<option value=\"$mode\" $sel>$label</option>";
                }
                ?>
            </select>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</div>
</div>
<script>
function send(mon,param,value){
    fetch("",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`monitor=${mon}&param=${param}&value=${value}`
    });
}
</script>

</body>
</html>
