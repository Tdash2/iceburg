<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 0)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 10)) { showAccessDenied(); exit; }

$id = $_GET['id'] ?? 0;
$id2 = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT ip, name FROM `devices` WHERE pluginID = 10 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($FS2_IP, $name2);
if (!$stmt->fetch()) { echo "No Device Found"; exit; }
$stmt->close();


$PARAMS = [
    "eParamID_Vid1VideoInput" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],
    "eParamID_Vid1OutputFormat_5923" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],
    "eParamID_Vid1YUVProcAmpEnable_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],
    "eParamID_Vid1RGBProcAmpEnable_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],

];

/* ================= FS2 API ================= */


function fs2_get_value($param) {
    global $FS2_IP;
    $url = "http://$FS2_IP/config?alt=json&action=get&paramid=" . urlencode($param);
    $ctx = stream_context_create(['http' => ['timeout' => 1]]);
    $res = @file_get_contents($url, false, $ctx);
    $json = $res ? json_decode($res, true) : null;
    return $json['value_name'] ?? null;
}



/* ================= API ================= */


    
if (fs2_get_value("eParamID_Vid1DetectedInputFormat") != null) {
    echo "Input 1 Format: " . fs2_get_value("eParamID_Vid1DetectedInputFormat");
    echo ", Output 1 Format: " . fs2_get_value("eParamID_Vid1ActualOutputFormat");
    
    echo "<br>";
    
    echo "Input 2 Format: " . fs2_get_value("eParamID_Vid2DetectedInputFormat");
    echo ", Output 2 Format: " . fs2_get_value("eParamID_Vid2ActualOutputFormat");
    
} else {
    header("HTTP/1.1 504 Gateway Timeout");
    exit;
}



?>


