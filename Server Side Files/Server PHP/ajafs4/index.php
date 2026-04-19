<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 0)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 11)) { showAccessDenied(); exit; }

$id = $_GET['id'] ?? 0;
$id2 = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT ip, name FROM `devices` WHERE pluginID = 11 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($FS2_IP, $name2);
if (!$stmt->fetch()) { echo "No Device Found"; exit; }
$stmt->close();

/* ================= CONFIG ================= */



$GROUP_NAMES = [
    1 => "Video 1 Processing",
    2 => "Video 2 Processing",
    3 => "Video 3 Processing",
    4 => "Video 4 Processing",
];

$SUBGROUP_NAMES = [
    1 => [
        1 => "Input Settings",
        2 => "ProcAmp",
        3 => "Red Channel",
        4 => "Green Channel",
        5 => "Blue Channel",
        
    ],
    2 => [
        1 => "Input Settings",
        2 => "ProcAmp",
        3 => "Red Channel",
        4 => "Green Channel",
        5 => "Blue Channel",
        
    ],
    3 => [
        1 => "Input Settings",
        2 => "ProcAmp",
        3 => "Red Channel",
        4 => "Green Channel",
        5 => "Blue Channel",
        
    ],
    4 => [
        1 => "Input Settings",
        2 => "ProcAmp",
        3 => "Red Channel",
        4 => "Green Channel",
        5 => "Blue Channel",
        
    ]
];

$PARAMS = [
    "eParamID_Vid1VideoInput" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],
    "eParamID_Vid1OutputFormat_5923" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],
    "eParamID_Vid1YUVProcAmpEnableSDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],
    "eParamID_Vid1RGBProcAmpEnableSDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],
    
    
    "eParamID_Vid1YUVProcAmpGainSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 2],
    "eParamID_Vid1YUVProcAmpBlackSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 2],
    "eParamID_Vid1YUVProcAmpHueSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 2],
    "eParamID_Vid1YUVProcAmpSatSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 2],
    

    "eParamID_Vid1RGBProcAmpGainRedSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 3],
    "eParamID_Vid1RGBProcAmpBlackRedSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 3],
    "eParamID_Vid1RGBProcAmpGammaRedSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 3],

    "eParamID_Vid1RGBProcAmpGainGreenSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 4],
    "eParamID_Vid1RGBProcAmpBlackGreenSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 4],
    "eParamID_Vid1RGBProcAmpGammaGreenSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 4],

    "eParamID_Vid1RGBProcAmpGainBlueSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 5],
    "eParamID_Vid1RGBProcAmpBlackBlueSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 5],
    "eParamID_Vid1RGBProcAmpGammaBlueSDI1" => ["type" => "slider", "group" => 1, "subgroup" => 5],
    
    
    
    "eParamID_Vid2VideoInput" => ["type" => "dropdown", "group" => 2, "subgroup" => 1],
    "eParamID_Vid2OutputFormat_5923" => ["type" => "dropdown", "group" => 2, "subgroup" => 1],
    "eParamID_Vid2YUVProcAmpEnableSDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 1],
    "eParamID_Vid2RGBProcAmpEnableSDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 1],
    
    
    "eParamID_Vid2YUVProcAmpGainSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 2],
    "eParamID_Vid2YUVProcAmpBlackSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 2],
    "eParamID_Vid2YUVProcAmpHueSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 2],
    "eParamID_Vid2YUVProcAmpSatSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 2],
    

    "eParamID_Vid2RGBProcAmpGainRedSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 3],
    "eParamID_Vid2RGBProcAmpBlackRedSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 3],
    "eParamID_Vid2RGBProcAmpGammaRedSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 3],

    "eParamID_Vid2RGBProcAmpGainGreenSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 4],
    "eParamID_Vid2RGBProcAmpBlackGreenSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 4],
    "eParamID_Vid2RGBProcAmpGammaGreenSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 4],

    "eParamID_Vid2RGBProcAmpGainBlueSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 5],
    "eParamID_Vid2RGBProcAmpBlackBlueSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 5],
    "eParamID_Vid2RGBProcAmpGammaBlueSDI2" => ["type" => "slider", "group" => 2, "subgroup" => 5],
    
    
    
    
    "eParamID_Vid3VideoInput" => ["type" => "dropdown", "group" => 3, "subgroup" => 1],
    "eParamID_Vid3OutputFormat_5923" => ["type" => "dropdown", "group" => 3, "subgroup" => 1],
    "eParamID_Vid3YUVProcAmpEnableSDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 1],
    "eParamID_Vid3RGBProcAmpEnableSDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 1],
    
    
    "eParamID_Vid3YUVProcAmpGainSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 2],
    "eParamID_Vid3YUVProcAmpBlackSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 2],
    "eParamID_Vid3YUVProcAmpHueSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 2],
    "eParamID_Vid3YUVProcAmpSatSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 2],
    

    "eParamID_Vid3RGBProcAmpGainRedSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 3],
    "eParamID_Vid3RGBProcAmpBlackRedSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 3],
    "eParamID_Vid3RGBProcAmpGammaRedSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 3],

    "eParamID_Vid3RGBProcAmpGainGreenSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 4],
    "eParamID_Vid3RGBProcAmpBlackGreenSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 4],
    "eParamID_Vid3RGBProcAmpGammaGreenSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 4],

    "eParamID_Vid3RGBProcAmpGainBlueSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 5],
    "eParamID_Vid3RGBProcAmpBlackBlueSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 5],
    "eParamID_Vid3RGBProcAmpGammaBlueSDI3" => ["type" => "slider", "group" => 3, "subgroup" => 5],
    
    
    
    
    
    "eParamID_Vid4VideoInput" => ["type" => "dropdown", "group" => 4, "subgroup" => 1],
    "eParamID_Vid4OutputFormat_5923" => ["type" => "dropdown", "group" => 4, "subgroup" => 1],
    "eParamID_Vid4YUVProcAmpEnableSDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 1],
    "eParamID_Vid4RGBProcAmpEnableSDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 1],
    
    
    "eParamID_Vid4YUVProcAmpGainSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 2],
    "eParamID_Vid4YUVProcAmpBlackSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 2],
    "eParamID_Vid4YUVProcAmpHueSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 2],
    "eParamID_Vid4YUVProcAmpSatSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 2],
    

    "eParamID_Vid4RGBProcAmpGainRedSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 3],
    "eParamID_Vid4RGBProcAmpBlackRedSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 3],
    "eParamID_Vid4RGBProcAmpGammaRedSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 3],

    "eParamID_Vid4RGBProcAmpGainGreenSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 4],
    "eParamID_Vid4RGBProcAmpBlackGreenSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 4],
    "eParamID_Vid4RGBProcAmpGammaGreenSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 4],

    "eParamID_Vid4RGBProcAmpGainBlueSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 5],
    "eParamID_Vid4RGBProcAmpBlackBlueSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 5],
    "eParamID_Vid4RGBProcAmpGammaBlueSDI4" => ["type" => "slider", "group" => 4, "subgroup" => 5],
    

  
];

/* ================= FS2 API ================= */

function fs2_fetch_desc() {
    global $FS2_IP;
    $url = "http://$FS2_IP/desc.json";
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $res = @file_get_contents($url, false, $ctx);
    return $res ? json_decode($res, true) : [];
}

function fs2_get_value($param) {
    global $FS2_IP;
    $url = "http://$FS2_IP/config?alt=json&action=get&paramid=" . urlencode($param);
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $res = @file_get_contents($url, false, $ctx);
    $json = $res ? json_decode($res, true) : null;
    return $json['value'] ?? null;
}

function fs2_set($param, $value) {
    global $FS2_IP;
    $url = "http://$FS2_IP/config?action=set&paramid=" .
        urlencode($param) . "&value=" . urlencode($value);
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    return @file_get_contents($url, false, $ctx);
}

/* ================= API ================= */

if (isset($_GET['api'])) {
    header("Content-Type: application/json");

    if ($_GET['api'] === "init") {

        $desc = fs2_fetch_desc();

        $map = [];
        foreach ($desc as $d) {
            $map[$d['param_id']] = $d;
        }

        $params = [];

        foreach ($PARAMS as $param => $cfg) {

            if (!isset($map[$param])) continue;
            $d = $map[$param];

            $params[$param] = [
                "type" => $cfg["type"],
                "group" => $cfg["group"] ?? 0,
                "subgroup" => $cfg["subgroup"] ?? 0,
                "value" => $d['value'] ?? $d['default_value'] ?? 0,
                "default" => $d['default_value'] ?? 0,
                "name" => $d['param_name'] ?? $param
            ];

            if ($cfg["type"] === "slider") {
                $params[$param]["min"] = $d['min_value'] ?? 0;
                $params[$param]["max"] = $d['max_value'] ?? 100;
            }

            if ($cfg["type"] === "dropdown") {
                $params[$param]["options"] = $d['enum_values'] ?? [];
            }
        }

        echo json_encode([
            "params" => $params,
            "groups" => $GROUP_NAMES,
            "subgroups" => $SUBGROUP_NAMES
        ]);
        exit;
    }

    if ($_GET['api'] === "values") {
        $out = [];
        foreach ($PARAMS as $param => $type) {
            $val = fs2_get_value($param);
            if ($val !== null) $out[$param] = $val;
        }
        echo json_encode($out);
        exit;
    }

    if ($_GET['api'] === "set") {
        $param = $_POST['param'] ?? '';
        $value = $_POST['value'] ?? '';

        if (!isset($PARAMS[$param])) {
            echo json_encode(["error" => "invalid param"]);
            exit;
        }

        fs2_set($param, $value);
        echo json_encode(["ok" => true]);
        exit;
    }
}
?>


<?php
include "../header.php";
?>

<!DOCTYPE html>
<html>
<head>
<title>FS4 Control</title>

<style>
body { font-family: Arial; background:#333; color:#eee; }

.group {
    margin: 15px 0;
    border: 1px solid #333;
    border-radius: 8px;
    overflow: hidden;
}

.group-header {
    background: #222;
    padding: 10px;
    cursor: pointer;
    font-weight: bold;
}

.group-body {
    padding: 10px;
    background: #111;
    display: flex;
    gap: 10px;
    overflow-x: auto;
}

.subgroup {
    min-width: 280px;
    border: 1px solid #333;
    border-radius: 8px;
    background: #0e0e0e;
    padding: 10px;
}

.subgroup-title {
    font-weight: bold;
    margin-bottom: 8px;
    color: #aaa;
    border-bottom: 1px solid #333;
    padding-bottom: 5px;
}

.app {
    width:95%;
      margin-left: auto;
  margin-right: auto;
  color: white;

}

.row { margin: 10px 0; }

input[type=range] { width: 240px; }
select { width: 240px; }
button { margin-left:10px; }
h2{color:#4db2ff!important;}
</style>

</head>

<body>
<div class="app">
<h2><?=$name2?></h2>
<p><?=$FS2_IP?></p>


<div  id="app"></div>
</div>
<script>

let config = {};

function init() {
    fetch("?id=<?php echo $id2; ?>&api=init")
        .then(r => r.json())
        .then(data => {
            config = data;
            buildUI();
            startPolling();
        });
}

function buildUI() {

    const container = document.getElementById("app");
    container.innerHTML = "";

    const groups = {};

    for (let param in config.params) {
        const p = config.params[param];

        const gid = p.group || 0;
        const sgid = p.subgroup || 0;

        if (!groups[gid]) groups[gid] = {};
        if (!groups[gid][sgid]) groups[gid][sgid] = [];

        groups[gid][sgid].push({ param, ...p });
    }

    Object.keys(groups).sort((a, b) => a - b).forEach(gid => {

        const groupDiv = document.createElement("div");
        groupDiv.className = "group";

        const header = document.createElement("div");
        header.className = "group-header";
        header.innerText = config.groups?.[gid] || ("Group " + gid);

        const body = document.createElement("div");
        body.className = "group-body";

        header.onclick = () => {
            body.style.display = body.style.display === "none" ? "flex" : "none";
        };

        groupDiv.appendChild(header);
        groupDiv.appendChild(body);

        Object.keys(groups[gid]).sort((a, b) => a - b).forEach(sgid => {

            const sub = document.createElement("div");
            sub.className = "subgroup";

            const title = document.createElement("div");
            title.className = "subgroup-title";
            title.innerText =
                config.subgroups?.[gid]?.[sgid] ||
                ("Subgroup " + sgid);
            title.style.color = "White";

            sub.appendChild(title);

            groups[gid][sgid].forEach(c => {

                const div = document.createElement("div");
                div.className = "row";

                const label = document.createElement("label");
                label.innerText = c.name +":";

                div.appendChild(label);

                // SLIDER
                if (c.type === "slider") {

                    const valueSpan = document.createElement("span");
                    valueSpan.id = c.param + "_val";
                    valueSpan.innerText = c.value ;
                    valueSpan.style.marginLeft = "6px";
                    valueSpan.style.color = "#aaa";

                    label.appendChild(valueSpan);

                    const input = document.createElement("input");
                    input.type = "range";
                    input.min = c.min;
                    input.max = c.max;
                    input.value = c.value;
                    input.id = c.param;

                    input.oninput = () => {
                        valueSpan.innerText = input.value;
                    };

                    input.onchange = () => send(c.param, input.value);

                    const btn = document.createElement("button");
                    btn.innerText = "Reset";
                    btn.onclick = () => send(c.param, c.default);
                    btn.style.background = "#46b8da";
                    btn.style.borderRadius = "2px";
                    btn.style.border = "none";

                    div.appendChild(input);
                    div.appendChild(btn);
                }

                // DROPDOWN (NO VALUE DISPLAY)
                if (c.type === "dropdown") {
                    linebreak = document.createElement("br");
                    div.appendChild(linebreak);
                    const select = document.createElement("select");
                    select.id = c.param;
                    select.style.color = "black";

                    (c.options || []).forEach(opt => {
                        const o = document.createElement("option");
                        o.value = opt.value;
                        o.text = opt.text;
                        o.style.color = "black";
                        if (opt.value == c.value) o.selected = true;
                        select.appendChild(o);
                    });

                    select.onchange = () => send(c.param, select.value);

                    div.appendChild(select);
                }

                sub.appendChild(div);
            });

            body.appendChild(sub);
        });

        container.appendChild(groupDiv);
    });
}

function send(param, value) {
    fetch("?id=<?php echo $id2; ?>&api=set", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `param=${encodeURIComponent(param)}&value=${encodeURIComponent(value)}`
    });
}

function startPolling() {
    setInterval(() => {
        fetch("?id=<?php echo $id2; ?>&api=values")
            .then(r => r.json())
            .then(data => {

                for (let param in data) {
                    const el = document.getElementById(param);
                    if (el) el.value = data[param];

                    const val = document.getElementById(param + "_val");
                    if (val) val.innerText = data[param];
                }
            });
    }, 1000);
}

window.onload = init;

</script>

</body>
</html>