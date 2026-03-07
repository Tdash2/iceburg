<?php
include "../config.php";
session_start();

// ----------------------------
// LICENSE + PERMISSIONS
// ----------------------------

if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 3)) { showAccessDenied(); exit; }

$id = $_GET['id'];

// ----------------------------
// GET DEVICE
// ----------------------------
$stmt = $conn->prepare("SELECT ip,name FROM `devices` WHERE pluginID = 3 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($ip, $devName);
if (!$stmt->fetch()) { echo("No Device Found"); exit; }
$stmt->close();

if (!$ip) { echo "No IP Provided!"; exit; }

// ------------------------------
// VIDEO INPUT OIDS
// ------------------------------
$oid_list = [
    5634 => "Path 1",
    5635 => "Path 2",
    5636 => "Path 3",
    6018 => "Path 4",
];

// ------------------------------
// VIDEO SOURCES
// ------------------------------
$sources = [
    0  => "Match Input",
    1  => "525i 59.94",
    2  => "625i 50",
    3  => "1280x720p 23.98",
    4  => "1280x720p 24",
    5  => "1280x720p 25",
    6  => "1280x720p 29.97",
    7  => "1280x720p 30",
    8  => "1280x720p 50",
    9  => "1280x720p 59.94",
    10 => "1280x720p 60",
    11 => "1920x1080i 50",
    12 => "1920x1080i 59.94",
    13 => "1920x1080i 60",
    14 => "1920x1080p 23.98",
    15 => "1920x1080p 24",
    16 => "1920x1080p 25",
    17 => "1920x1080p 29.97",
    18 => "1920x1080p 30",
    19 => "2048x1080p 23.98",
    20 => "2048x1080p 24",
    21 => "2048x1080p 25",
    22 => "1920x1080psf 23.98",
    23 => "1920x1080psf 24",
    24 => "1920x1080psf 25",
    25 => "1920x1080psf 29.97",
    26 => "1920x1080psf 30",
    27 => "1920x1080p 50 A",
    28 => "1920x1080p 59.94 A",
    29 => "1920x1080p 60 A",
    30 => "2048x1080p 50 A",
    31 => "2048x1080p 59.94 A",
    32 => "2048x1080p 60 A",
];


// ------------------------------
// HANDLE SAVE
// ------------------------------
$current_values = [];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['oids'])) {

    if ((int)$_SESSION['user_permissions'] < 2) {
        echo json_encode(['success'=>false,'redirect'=>"/9905-mpx/?id={$id}"]);
        exit;
    }

    $allSuccess = true;

    foreach ($_POST['oids'] as $oid => $value) {
        $oid = intval($oid);
        $value = intval($value);
       $url = "http://".$ip.":9002/setOid?oid={$oid}&value={$value}";
$context = stream_context_create([
    'http' => [
        'timeout' => 2, // 2 seconds
    ]
]);
if (@file_get_contents($url, false, $context) === false) {
    $allSuccess = false;
}
    }

    echo json_encode(['success'=>$allSuccess]);
    exit;
}

// ------------------------------
// GET CURRENT VALUES
// ------------------------------
foreach ($oid_list as $oid => $name) {
$context = stream_context_create([
    'http' => [
        'timeout' => 1, // 2 seconds timeout
    ]
]);
$json = @file_get_contents("http://".$ip.":9002/getOid?oid={$oid}", false, $context);

    if ($json) {
        $data = json_decode($json,true);
        $current_values[$oid] =
            $data["data_updates"]["oids"][0]["data_value"] ?? null;
    }
}

// ------------------------------
// GET PATH NAMES
// ------------------------------
function getOidName($ip, $oid) {
$context = stream_context_create([
    'http' => [
        'timeout' => 1, // 2 seconds timeout
    ]
]);
$json = @file_get_contents("http://".$ip.":9002/getOid?oid={$oid}", false, $context);

    if ($json) {
        $data = json_decode($json,true);
        return $data["data_updates"]["oids"][0]["data_value"] ?? "";
    }
    return "";
}

$pathNames = [
    5634 => getOidName($ip,4866),
    5635 => getOidName($ip,4867),
    5636 => getOidName($ip,4868),
    5637 => getOidName($ip,4869),
];

include "../header.php";
?>
<!DOCTYPE html>
<html>
<head>
<title>9905-MPX Video Format</title>

<style>
body { font-family: Arial, sans-serif; background: #232323; color: #eee; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 4px solid #555; }
td { padding: 10px; border: 4px solid #555; }
input.search-box { width: 100%; box-sizing: border-box; color: #000; }
.pending-change { background: rgb(153 255 156); border: 1px solid #990000; }
.suggestions { position:absolute; background:#fff; width:100%; z-index:10; display:none; }
.suggestion-item {
    cursor: pointer;
    padding: 4px;
    background: #ffffff;
    color: #000000;   /* <-- force black text */
}
.suggestion-item:hover {
    background:#0078ff;
    color: #ffffff;
}
.input-wrapper { position:relative; }
button { padding:6px 12px; font-weight:bold; }
#takeButton{display:none;background:#48c848;color:#fff;}
#reloadButton{background:#46b8da;color:#fff;}
</style>
<style>
body { font-family: Arial, sans-serif; background: #232323; color: #eee; }
h1 { color: #4db2ff; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 4px solid #555; }
th, td { padding: 8px; border: 4px solid #555; text-align: left; }
input.search-box { width: 100%; box-sizing: border-box; color: #000; }
.pending-change { background: rgb(153 255 156) !important; border: 1px solid #990000 !important; color: #000; }
.suggestions { position: absolute; top: 100%; left: 0; background: #fff; border: 2px solid #000; max-height: 150px; overflow-y: auto; width: 100%; z-index: 10; color: #000; box-sizing: border-box; }
.suggestion-item { padding: 0px; cursor: pointer; background: antiquewhite; }
.suggestion-item:hover, .suggestion-active { background: #0078ff; color: white; }
.input-wrapper { position: relative; }
button#takeButton { padding: 6px 12px; font-size: 14px; border-radius: 4px; background:#48c848; border:none; color:#fff; font-weight:bold; cursor:pointer; display:none; }
button#reloadButton {
    padding: 6px 12px;
    font-size: 14px;
    line-height: 1.42857143;
    border-radius: 4px;
    background:#46b8da;
    border:none;
    color:#fff;
    font-weight:bold;
    cursor:pointer;
   
    

}
</style>
</head>
<body>
<div class="container">
<h2><?php echo htmlspecialchars($devName); ?> - Video Format</h2>
<p>FrameSync : <strong><?php echo htmlspecialchars($ip); ?></strong></p>

<button id="reloadButton" onclick="location.reload()">Refresh</button>
<button id="takeButton">Save Format</button>

<table>
<?php foreach ($oid_list as $oid=>$label): ?>
<tr>
    <td>
        <strong><?php echo htmlspecialchars($pathNames[$oid] ?: $label); ?></strong>
        <div class="input-wrapper">
            <input class="search-box" data-oid="<?php echo $oid; ?>" autocomplete="off">
            <div class="suggestions"></div>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>
<script>
let sources = <?php echo json_encode($sources); ?>;
let currentValues = <?php echo json_encode($current_values); ?>;

// Init inputs
document.querySelectorAll(".search-box").forEach(input=>{
    let oid = parseInt(input.dataset.oid);
    if(currentValues[oid] !== undefined){
        input.value = sources[currentValues[oid]] || "";
        input.dataset.selectedKey = currentValues[oid];
    }
});

// Show Take when changes exist
function updateTake(){
    let changed = [...document.querySelectorAll(".search-box")].some(i=>i.classList.contains("pending-change"));
    document.getElementById("takeButton").style.display = changed ? "inline-block" : "none";
}

// Autocomplete
// --- Autocomplete (FIXED open/close logic) ---
// --- Autocomplete with keyboard support ---
document.querySelectorAll(".search-box").forEach(input => {
    const wrapper = input.parentElement;
    const sug = wrapper.querySelector(".suggestions");
    let activeIndex = -1;

    function renderSuggestions() {
        const val = (input.value || "").toLowerCase();
        sug.innerHTML = "";
        activeIndex = -1;

        Object.entries(sources).forEach(([k, label]) => {
            if (!val || label.toLowerCase().includes(val)) {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = label;
                div.dataset.key = k;
                // click selects
                div.addEventListener("click", (ev) => {
                    ev.stopPropagation();
                    selectItem(input, k);
                    hideSuggestions();
                    input.focus();
                });
                sug.appendChild(div);
            }
        });

        if (sug.children.length) showSuggestions();
        else hideSuggestions();
    }

    function showSuggestions() {
        sug.style.display = "block";
    }
    function hideSuggestions() {
        sug.style.display = "none";
        activeIndex = -1;
        updateActive();
    }

    function updateActive() {
        const items = sug.querySelectorAll(".suggestion-item");
        items.forEach((it, idx) => {
            if (idx === activeIndex) it.classList.add("suggestion-active");
            else it.classList.remove("suggestion-active");
        });
        // ensure active visible (scroll into view)
        const activeEl = sug.querySelector(".suggestion-active");
        if (activeEl) activeEl.scrollIntoView({ block: "nearest" });
    }

    // keyboard handling
    input.addEventListener("keydown", (e) => {
        const items = sug.querySelectorAll(".suggestion-item");
        if (!items.length && (e.key === "ArrowDown" || e.key === "ArrowUp" || e.key === "Enter")) {
            return; // nothing to do
        }

        if (e.key === "ArrowDown") {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % items.length;
            updateActive();
            showSuggestions();
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            activeIndex = (activeIndex - 1 + items.length) % items.length;
            updateActive();
            showSuggestions();
        } else if (e.key === "Enter") {
            // if an item is active, select it; otherwise if there's exactly one match, select it
            e.preventDefault();
            if (activeIndex >= 0 && items[activeIndex]) {
                const key = items[activeIndex].dataset.key;
                selectItem(input, key);
                hideSuggestions();
            } else if (items.length === 1) {
                const key = items[0].dataset.key;
                selectItem(input, key);
                hideSuggestions();
            } else {
                // No active item — let it be (or you can hide suggestions)
                hideSuggestions();
            }
        } else if (e.key === "Tab") {
            // behave like Enter if suggestion active
            if (activeIndex >= 0 && items[activeIndex]) {
                const key = items[activeIndex].dataset.key;
                selectItem(input, key);
            }
            hideSuggestions();
        } else if (e.key === "Escape") {
            hideSuggestions();
            input.blur();
        }
    });

    // show suggestions on focus and input
    input.addEventListener("focus", renderSuggestions);
    input.addEventListener("input", renderSuggestions);

    // stop click inside wrapper from closing the list
    wrapper.addEventListener("click", (ev) => {
        ev.stopPropagation();
    });
});

// global click closes dropdowns
document.addEventListener("click", function(e) {
    document.querySelectorAll(".suggestions").forEach(box => {
        if (!box.parentElement.contains(e.target)) {
            box.style.display = "none";
        }
    });
});


function selectItem(input,key){
    input.value = sources[key];
    input.dataset.selectedKey = key;
    let oid = parseInt(input.dataset.oid);
    if(currentValues[oid]!=key) input.classList.add("pending-change");
    else input.classList.remove("pending-change");
    updateTake();
}

document.getElementById("takeButton").onclick = async ()=>{
    let oids = {};
    document.querySelectorAll(".pending-change").forEach(i=>{
        oids[i.dataset.oid]=i.dataset.selectedKey;
    });

    let p = new URLSearchParams();
    for(const k in oids) p.append(`oids[${k}]`,oids[k]);

    let r = await fetch("",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"},
        body:p.toString()
    });

    let j = await r.json();
    if(j.success){
        for(const k in oids){
            currentValues[k]=oids[k];
            document.querySelector(`[data-oid="${k}"]`).classList.remove("pending-change");
        }
        updateTake();
    }
};
</script>

</body>
</html>
