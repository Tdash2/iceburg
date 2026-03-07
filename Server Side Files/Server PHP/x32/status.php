<?php
include "../config.php";
session_start();



// Check permissions
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn,1, 1)) {
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



function parse_xinfo_response($raw)
{
    // Remove the OSC address (/xinfo or /info)
    $raw = preg_replace('/^\/[a-z]+[\x00]*/i', '', $raw);

    // Remove the type tag (e.g., ",ssss")
    $raw = preg_replace('/^,s+[\x00]*/', '', $raw);

    // Now split on OSC NULL boundaries
    $parts = preg_split("/\x00+/", $raw);

    // Remove empty entries
    $parts = array_values(array_filter($parts));

    // X32 `/xinfo` structure:
    $mapping = [
        "server_version",
        "server_name",
        "console_model",
        "console_version",
        "network_address",
        "network_name",
        "console_model_repeat",
        "console_version_repeat"
    ];

    $result = [];

    for ($i = 0; $i < count($parts) && $i < count($mapping); $i++) {
        $result[$mapping[$i]] = $parts[$i];
    }

    return $result;
}


$addr = "/xinfo";
$enum = "";
$back = send_recv(osc($addr, "i", $enum));

if (!$back) {
    header("HTTP/1.1 504 Gateway Timeout");
    exit;
} else {
$parsed = parse_xinfo_response($back);

$x32version   = $parsed['console_version'] ?? "Unknown";
$friendlyname = $parsed['server_name'] ?? "Unknown";


echo "X32 Version: " . $x32version . "<br>";
echo "Friendly Name: " . $friendlyname . "<br>";
}


?>

<!DOCTYPE html>

