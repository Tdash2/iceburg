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

$hid= $_GET['id'];

$stmt = $conn->prepare("SELECT ip,name FROM `devices` WHERE pluginID = 9 AND id=?");
$stmt->bind_param("i", $hid);
$stmt->execute();
$stmt->bind_result($camera_ip,$devicename);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();


if ($camera_ip == null){
echo "No Ip Provided!";
exit;
}



function cam($ip, $option, $payload)
{
    $ch = curl_init("http://$ip/camera?option=$option&login_check_flag=1");

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
    "group"=>"camera",
    "opt"=>"get_image_info"
]);

$imageData = json_decode($imageInfo, true);

$settings["brightness"] = $imageData["data"]["brightness"] ?? $settings["brightness"];
$settings["contrast"] = $imageData["data"]["contrast"] ?? $settings["contrast"];
$settings["sharpness"] = $imageData["data"]["sharpness"] ?? $settings["sharpness"];

// ======================
// HANDLE AUTO-UPDATE JSON POST
// ======================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents('php://input'), true);

    // EXPOSURE: shutter + gain
    $shutterId = intval($input["shutter"]);
    $gain = intval($input["gain"]);
    cam($camera_ip, "setinfo", [
        "group"=>"camera",
        "opt"=>"set_exposure_info",
        "data"=>[
            "shutter"=>["selected_id"=>$shutterId],
            "gain"=>$gain
        ]
    ]);

    // WHITE BALANCE
    $wbId = intval($input["wb"]);
    cam($camera_ip, "setinfo", [
        "group"=>"camera",
        "opt"=>"set_white_balance_info",
        "data"=>[
            "var"=>["selected_id"=>$wbId]
        ]
    ]);

    // COLOR: saturation + hue
    $saturation = intval($input["saturation"]);
    $hue = intval($input["hue"]);
    cam($camera_ip, "setinfo", [
        "group"=>"camera",
        "opt"=>"set_white_balance_info",
        "data"=>[
            "saturation"=>$saturation,
            "hue"=>$hue
        ]
    ]);

    // IMAGE SETTINGS: brightness, contrast, sharpness
    $brightness = intval($input["brightness"]);
    $contrast = intval($input["contrast"]);
    $sharpness = intval($input["sharpness"]);

    cam($camera_ip, "setinfo", [
        "group"=>"camera",
        "opt"=>"set_image_info",
        "data"=>[
            "brightness"=>$brightness,
            "contrast"=>$contrast,
            "sharpness"=>$sharpness
        ]
    ]);

    echo "OK";
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
<title>ZowieCAM Controller</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.42857143;
    color: #fff!important;
    background-color: #1e1e1e!important;
}
h2{color:#4db2ff!important;}

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
<?php include "../header.php"; ?>


<div class="container">

<h2><?=$devicename?></h2>
<p><?=$x32?></p>
<br><div class="box" style="
    background: #2d2d2d;
    padding: 10px;
    border-radius: 8px;
">
<!-- SHUTTER -->
Shutter Speed<br>
<select style="
    color: black;" id="shutter">
<?php foreach($shutterOptions as $option): ?>
    <option value="<?= $option['id'] ?>" <?= ($option['name'] == $settings["shutter"]) ? "selected" : "" ?>>
        <?= $option['name'] ?>
    </option>
<?php endforeach; ?>
</select><br><br>

<!-- WHITE BALANCE -->
White Balance<br>
<select style="
    color: black;" id="wb">
<?php foreach($wbOptions as $option): ?>
    <?php $clean = intval(str_replace("K","",$option['name'])); ?>
    <option value="<?= $option['id'] ?>" <?= ($clean == $settings["wb"]) ? "selected" : "" ?>>
        <?= $option['name'] ?>
    </option>
<?php endforeach; ?>
</select><br>

<!-- Gain -->
<label id="gainLabel">Gain: <?= $settings["gain"] ?></label>
<input type="range" id="gain" min="0" max="16" value="<?= $settings["gain"] ?>" 
       oninput="document.getElementById('gainLabel').textContent='Gain: ' + this.value; updateCamera()">
<br>

<!-- Saturation -->
<label id="saturationLabel">Saturation: <?= $settings["saturation"] ?></label>
<input type="range" id="saturation" min="1" max="100" value="<?= $settings["saturation"] ?>" 
       oninput="document.getElementById('saturationLabel').textContent='Saturation: ' + this.value; updateCamera()">
<br>

<!-- Hue -->
<label id="hueLabel">Hue: <?= $settings["hue"] ?></label>
<input type="range" id="hue" min="-180" max="180" value="<?= $settings["hue"] ?>" 
       oninput="document.getElementById('hueLabel').textContent='Hue: ' + this.value; updateCamera()">
<br>

<!-- Brightness -->
<label id="brightnessLabel">Brightness: <?= $settings["brightness"] ?></label>
<input type="range" id="brightness" min="0" max="8" value="<?= $settings["brightness"] ?>" 
       oninput="document.getElementById('brightnessLabel').textContent='Brightness: ' + this.value; updateCamera()">
<br>

<!-- Contrast -->
<label id="contrastLabel">Contrast: <?= $settings["contrast"] ?></label>
<input type="range" id="contrast" min="0" max="15" value="<?= $settings["contrast"] ?>" 
       oninput="document.getElementById('contrastLabel').textContent='Contrast: ' + this.value; updateCamera()">
<br>

<!-- Sharpness -->
<label id="sharpnessLabel">Sharpness: <?= $settings["sharpness"] ?></label>
<input type="range" id="sharpness" min="0" max="10" value="<?= $settings["sharpness"] ?>" 
       oninput="document.getElementById('sharpnessLabel').textContent='Sharpness: ' + this.value; updateCamera()">
<br>


</div>
</div>
<script>
function updateCamera() {
    const data = {
        shutter: document.getElementById("shutter").value,
        wb: document.getElementById("wb").value,
        gain: document.getElementById("gain").value,
        saturation: document.getElementById("saturation").value,
        hue: document.getElementById("hue").value,
        brightness: document.getElementById("brightness").value,
        contrast: document.getElementById("contrast").value,
        sharpness: document.getElementById("sharpness").value
    };

    fetch("<?= $_SERVER['PHP_SELF']."?id=".$hid ?>", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(data)
    })
    .then(res => res.text())
    .then(console.log)
    .catch(console.error);
}

document.getElementById("shutter").addEventListener("change", updateCamera);
document.getElementById("wb").addEventListener("change", updateCamera);
</script>


</body>
</html>