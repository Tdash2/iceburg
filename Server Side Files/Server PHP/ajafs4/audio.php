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
    1 => "Audio 1 Processing",
    2 => "Audio 2 Processing",
    3 => "Audio 3 Processing",
    4 => "Audio 4 Processing",
];

$SUBGROUP_NAMES = [
    1 => [
        1 => "Input Settings",
        2 => "Channel Map 1-4",
        3 => "Channel Map 5-8",
        4 => "Channel Map 9-12",
        5 => "Channel Map 13-16",
        
    ],
    2 => [
        1 => "Input Settings",
        2 => "Channel Map 1-4",
        3 => "Channel Map 5-8",
        4 => "Channel Map 9-12",
        5 => "Channel Map 13-16",
        
    ],
    3 => [
        1 => "Input Settings",
        2 => "Channel Map 1-4",
        3 => "Channel Map 5-8",
        4 => "Channel Map 9-12",
        5 => "Channel Map 13-16",
        
    ],
    4 => [
        1 => "Input Settings",
        2 => "Channel Map 1-4",
        3 => "Channel Map 5-8",
        4 => "Channel Map 9-12",
        5 => "Channel Map 13-16",
        
    ]
];

$PARAMS = [
    "eParamID_AudioOutputSelect_Vid1Embed" => ["type" => "dropdown", "group" => 1, "subgroup" => 1],

    "eParamID_AudioOutputCh1_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 2],
    "eParamID_AudioOutputCh2_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 2],
    "eParamID_AudioOutputCh3_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 2],
    "eParamID_AudioOutputCh4_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 2],

    "eParamID_AudioOutputCh5_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 3],
    "eParamID_AudioOutputCh6_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 3],
    "eParamID_AudioOutputCh7_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 3],
    "eParamID_AudioOutputCh8_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 3],

    "eParamID_AudioOutputCh9_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 4],
    "eParamID_AudioOutputCh10_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 4],
    "eParamID_AudioOutputCh11_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 4],
    "eParamID_AudioOutputCh12_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 4],

    "eParamID_AudioOutputCh13_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 5],
    "eParamID_AudioOutputCh14_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 5],
    "eParamID_AudioOutputCh15_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 5],
    "eParamID_AudioOutputCh16_SDI1" => ["type" => "dropdown", "group" => 1, "subgroup" => 5],
    
    
    "eParamID_AudioOutputSelect_Vid2Embed" => ["type" => "dropdown", "group" => 2, "subgroup" => 1],

    "eParamID_AudioOutputCh1_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 2],
    "eParamID_AudioOutputCh2_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 2],
    "eParamID_AudioOutputCh3_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 2],
    "eParamID_AudioOutputCh4_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 2],

    "eParamID_AudioOutputCh5_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 3],
    "eParamID_AudioOutputCh6_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 3],
    "eParamID_AudioOutputCh7_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 3],
    "eParamID_AudioOutputCh8_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 3],

    "eParamID_AudioOutputCh9_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 4],
    "eParamID_AudioOutputCh10_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 4],
    "eParamID_AudioOutputCh11_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 4],
    "eParamID_AudioOutputCh12_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 4],

    "eParamID_AudioOutputCh13_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 5],
    "eParamID_AudioOutputCh14_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 5],
    "eParamID_AudioOutputCh15_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 5],
    "eParamID_AudioOutputCh16_SDI2" => ["type" => "dropdown", "group" => 2, "subgroup" => 5],
    
    
    "eParamID_AudioOutputSelect_Vid3Embed" => ["type" => "dropdown", "group" => 3, "subgroup" => 1],

    "eParamID_AudioOutputCh1_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 2],
    "eParamID_AudioOutputCh2_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 2],
    "eParamID_AudioOutputCh3_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 2],
    "eParamID_AudioOutputCh4_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 2],

    "eParamID_AudioOutputCh5_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 3],
    "eParamID_AudioOutputCh6_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 3],
    "eParamID_AudioOutputCh7_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 3],
    "eParamID_AudioOutputCh8_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 3],

    "eParamID_AudioOutputCh9_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 4],
    "eParamID_AudioOutputCh10_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 4],
    "eParamID_AudioOutputCh11_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 4],
    "eParamID_AudioOutputCh12_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 4],

    "eParamID_AudioOutputCh13_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 5],
    "eParamID_AudioOutputCh14_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 5],
    "eParamID_AudioOutputCh15_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 5],
    "eParamID_AudioOutputCh16_SDI3" => ["type" => "dropdown", "group" => 3, "subgroup" => 5],
    
    
    
    "eParamID_AudioOutputSelect_Vid4Embed" => ["type" => "dropdown", "group" => 4, "subgroup" => 1],

    "eParamID_AudioOutputCh1_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 2],
    "eParamID_AudioOutputCh2_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 2],
    "eParamID_AudioOutputCh3_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 2],
    "eParamID_AudioOutputCh4_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 2],

    "eParamID_AudioOutputCh5_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 3],
    "eParamID_AudioOutputCh6_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 3],
    "eParamID_AudioOutputCh7_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 3],
    "eParamID_AudioOutputCh8_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 3],

    "eParamID_AudioOutputCh9_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 4],
    "eParamID_AudioOutputCh10_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 4],
    "eParamID_AudioOutputCh11_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 4],
    "eParamID_AudioOutputCh12_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 4],

    "eParamID_AudioOutputCh13_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 5],
    "eParamID_AudioOutputCh14_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 5],
    "eParamID_AudioOutputCh15_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 5],
    "eParamID_AudioOutputCh16_SDI4" => ["type" => "dropdown", "group" => 4, "subgroup" => 5],

  
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


.input-wrapper {
    position: relative;
}

.suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    border: 2px solid #000;
    max-height: 150px;
    overflow-y: auto;
    width: 240px;
    z-index: 10;
    color: #000;
}

.suggestion-item {
    padding: 5px;
    cursor: pointer;
    background: antiquewhite;
}

.suggestion-item:hover {
    background: #0078ff;
    color: white;
}

.search-box {
    color: #000 !important;
}
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
let madiNames = {};
let dropdownMap = {};
let userEditing = {};
let lastSent = {};
let pollInterval = null;

// -----------------------------
// INIT
// -----------------------------
function init() {
    fetch("?id=<?php echo $id2; ?>&api=init")
        .then(r => r.json())
        .then(data => {
            config = data;
            return fetch("/x32/getallnames.php?id=117");
        })
        .then(r => r.json())
        .then(data => {
            madiNames = {};
            if (Array.isArray(data)) {
                data.forEach(item => {
                    madiNames[item.number] = item.name;
                });
            }
            buildUI();
            startPolling();
        })
        .catch(err => {
            console.error("Init failed:", err);
            buildUI();
            startPolling();
        });
}

// -----------------------------
// LABEL RESOLVE
// -----------------------------
function resolveLabel(text) {
    const match = text.match(/MADIBNC\s*CH\s*(\d+)/i);
    if (match) {
        const ch = parseInt(match[1]);
        if (madiNames[ch]) return madiNames[ch];
    }
    return text;
}

// -----------------------------
// BUILD UI
// -----------------------------
function buildUI() {

    const container = document.getElementById("app");
    container.innerHTML = "";

    dropdownMap = {};

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
            body.style.display = (body.style.display === "none" || body.style.display === "")
                ? "flex"
                : "none";
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

            sub.appendChild(title);

            groups[gid][sgid].forEach(c => {

                const div = document.createElement("div");
                div.className = "row";

                const label = document.createElement("label");
                label.innerText = c.name + ":";
                div.appendChild(label);

                // ---------------- SLIDER ----------------
                if (c.type === "slider") {

                    const valueSpan = document.createElement("span");
                    valueSpan.id = c.param + "_val";
                    valueSpan.innerText = c.value;
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

                    div.appendChild(input);

                    const btn = document.createElement("button");
                    btn.innerText = "Reset";
                    btn.onclick = () => send(c.param, c.default);
                    div.appendChild(btn);
                }

                // ---------------- DROPDOWN ----------------
if (c.type === "dropdown") {

    const wrapper = document.createElement("div");
    wrapper.className = "input-wrapper";
    wrapper.style.position = "relative";

    const input = document.createElement("input");
    input.type = "text";
    input.className = "search-box";
    input.autocomplete = "off";
    input.dataset.param = c.param;

    const dropdown = document.createElement("div");
    dropdown.className = "suggestions";

    // ?? MOVE TO BODY (portal)
    dropdown.style.position = "absolute";
    dropdown.style.zIndex = "999999";
    dropdown.style.display = "none";
    dropdown.style.background = "#fff";
    dropdown.style.border = "2px solid #000";
    dropdown.style.maxHeight = "200px";
    dropdown.style.overflowY = "auto";
    dropdown.style.width = "240px";
    dropdown.style.color = "#000";

    document.body.appendChild(dropdown);

    const options = (c.options || []).map(opt => ({
        key: opt.value,
        label: resolveLabel(opt.text)
    }));

    dropdownMap[c.param] = options;

    let highlightedIndex = -1;

    const current = options.find(o => o.key == c.value);
    input.value = current ? current.label : "";
    input.dataset.selectedKey = c.value;

    userEditing[c.param] = false;

    function positionDropdown() {
        const rect = input.getBoundingClientRect();
        dropdown.style.left = rect.left + window.scrollX + "px";
        dropdown.style.top = rect.bottom + window.scrollY + "px";
    }

    function close() {
        dropdown.style.display = "none";
        highlightedIndex = -1;
    }

    function select(opt) {
        if (!opt) return;

        input.value = opt.label;
        input.dataset.selectedKey = opt.key;

        userEditing[c.param] = false;
        send(c.param, opt.key);

        close();
    }

    function render(text) {

        dropdown.innerHTML = "";

        const filtered = options.filter(o =>
            o.label.toLowerCase().includes(text.toLowerCase())
        );

        if (!filtered.length) {
            close();
            return;
        }

        filtered.forEach((opt, idx) => {

            const item = document.createElement("div");
            item.textContent = opt.label;
            item.style.padding = "5px";
            item.style.cursor = "pointer";

            if (idx === highlightedIndex) {
                item.style.background = "#0078ff";
                item.style.color = "white";
            }

            item.onclick = () => select(opt);

            dropdown.appendChild(item);
        });

        positionDropdown();
        dropdown.style.display = "block";
    }

    function getFiltered() {
        return options.filter(o =>
            o.label.toLowerCase().includes(input.value.toLowerCase())
        );
    }

    input.addEventListener("focus", () => {
        userEditing[c.param] = true;
        render(input.value);
    });

    input.addEventListener("input", () => {
        userEditing[c.param] = true;
        highlightedIndex = -1;
        render(input.value);
    });

    input.addEventListener("keydown", (e) => {

        const filtered = getFiltered();

        if (e.key === "ArrowDown") {
            e.preventDefault();
            highlightedIndex = Math.min(highlightedIndex + 1, filtered.length - 1);
            render(input.value);
        }

        else if (e.key === "ArrowUp") {
            e.preventDefault();
            highlightedIndex = Math.max(highlightedIndex - 1, 0);
            render(input.value);
        }

        else if (e.key === "Enter") {
            e.preventDefault();
            select(filtered[highlightedIndex] || filtered[0]);
        }

        else if (e.key === "Escape") {
            close();
        }
    });

    input.addEventListener("blur", () => {
        setTimeout(() => {

            userEditing[c.param] = false;

            const match = options.find(o =>
                o.label.toLowerCase() === input.value.toLowerCase()
            );

            if (match) send(c.param, match.key);

            close();

        }, 150);
    });

    document.addEventListener("click", (e) => {
        if (e.target !== input) close();
    });

    window.addEventListener("resize", close);
    window.addEventListener("scroll", close);

    wrapper.appendChild(input);
    div.appendChild(wrapper);
}

                sub.appendChild(div);
            });

            body.appendChild(sub);
        });

        container.appendChild(groupDiv);
    });
}

// -----------------------------
// SEND
// -----------------------------
function send(param, value) {

    lastSent[param] = value;

    fetch("?id=<?php echo $id2; ?>&api=set", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `param=${encodeURIComponent(param)}&value=${encodeURIComponent(value)}`
    });
}

// -----------------------------
// POLLING (SAFE SYNC)
// -----------------------------
function startPolling() {

    if (pollInterval) clearInterval(pollInterval);

    pollInterval = setInterval(() => {

        fetch("?id=<?php echo $id2; ?>&api=values")
            .then(r => r.json())
            .then(data => {

                for (let param in data) {

                    // SLIDERS
                    const slider = document.getElementById(param);
                    if (slider && !userEditing[param]) {
                        slider.value = data[param];
                    }

                    const val = document.getElementById(param + "_val");
                    if (val && !userEditing[param]) {
                        val.innerText = data[param];
                    }

                    // DROPDOWNS
                    const options = dropdownMap[param] || [];

                    document.querySelectorAll(`input.search-box[data-param="${param}"]`)
                        .forEach(input => {

                            if (userEditing[param]) return;

                            const match = options.find(o => o.key == data[param]);
                            if (!match) return;

                            if (input.dataset.selectedKey != data[param]) {
                                input.value = match.label;
                                input.dataset.selectedKey = data[param];
                            }
                        });
                }
            });

    }, 5000);
}


window.onload = init;


</script>

</body>
</html>