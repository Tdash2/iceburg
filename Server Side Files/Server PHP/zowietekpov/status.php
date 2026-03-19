<?php
include "../config.php";
session_start();



// Check permissions
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 1, 9)) {
    showAccessDenied();
    exit;
}


?>
<?php
// CONFIG

$VIDEHub_HOST = "";

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip,name FROM `devices` WHERE pluginID = 9 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($x32,$devicename);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();


if ($x32 == null){
echo "No Ip Provided!";
exit;
}

$camera_ip = $x32;

function cam($ip, $option, $payload)
{
    $ch = curl_init("http://$ip/video?option=$option&login_check_flag=1");

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // seconds to wait for connection
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);        // max seconds for the request

    $res = curl_exec($ch);

    if ($res === false) {
        $res = "CURL ERROR: " . curl_error($ch);
            header("HTTP/1.1 504 Gateway Timeout");
    }

    curl_close($ch);
    return $res;
}

// ======================
// DEFAULTS
// ======================
$settings = [
    "shutter" => "1/60",
    "gain" => 10,
    "wb" => 4500,
    "saturation" => 50,
    "hue" => 0,
    "brightness" => 4,
    "contrast" => 8,
    "sharpness" => 5
];

$wbOptions = [];
$shutterOptions = [];

// ======================
// GET EXPOSURE
// ======================
$exp = cam($camera_ip, "getinfo", [
    "group"=>"camera",
    "opt"=>"get_exposure_info"
]);

$expData = json_decode($exp, true);

if(isset($expData["data"]["shutter"]["shutter_list"])) {
    $rawShutters = $expData["data"]["shutter"]["shutter_list"];
    $shutterOptions = [];

    foreach($rawShutters as $k => $v) {
        if (is_array($v) && isset($v['id'], $v['name'])) {
            $shutterOptions[] = $v;
        } else {
            $shutterOptions[] = ['id'=>$k, 'name'=>$v];
        }
    }

    $selectedId = $expData["data"]["shutter"]["selected_id"] ?? 0;
    $settings["shutter"] = $shutterOptions[$selectedId]['name'] ?? $settings["shutter"];
}

// Gain
$settings["gain"] = $expData["data"]["gain"] ?? $settings["gain"];

// ======================
// GET WHITE BALANCE
// ======================
$wb = cam($camera_ip, "getinfo", [
    "group"=>"camera",
    "opt"=>"get_white_balance_info"
]);

$wbData = json_decode($wb, true);

if(isset($wbData["data"]["var"]["var_list"])) {
    $rawWBs = $wbData["data"]["var"]["var_list"];
    $wbOptions = [];

    foreach($rawWBs as $k => $v) {
        $wbOptions[] = ['id'=>$k, 'name'=>$v];
    }

    $selectedId = $wbData["data"]["var"]["selected_id"] ?? 0;
    $settings["wb"] = intval(str_replace("K", "", $wbOptions[$selectedId]['name']));
    $settings["saturation"] = $wbData["data"]["saturation"] ?? $settings["saturation"];
    $settings["hue"] = $wbData["data"]["hue"] ?? $settings["hue"];
}

// ======================
// GET IMAGE INFO (Brightness, Contrast, Sharpness)
// ======================
$imageInfo = cam($camera_ip, "getinfo", [
    "group"=>"ndi",
    "opt"=>"get_ndi_info"
]);

$imageData = json_decode($imageInfo, true);

$settings["machinename"] = $imageData["data"]["machinename"] ;

// ======================
// HANDLE AUTO-UPDATE JSON POST
// ======================

Echo "NDI Name: ". $settings["machinename"];

?>

