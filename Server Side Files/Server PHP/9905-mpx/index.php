
<?php
include "../config.php";
session_start();


if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 3)) { showAccessDenied(); exit; }

$id = $_GET['id'];

// Get device IP
$stmt = $conn->prepare("SELECT ip,name,madisorce FROM `devices` WHERE pluginID = 3 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($ip, $x32name,$madisorce);
if (!$stmt->fetch()) { echo("No Device Found"); exit; }
$stmt->close();

if (!$ip) { echo "No IP Provided!"; exit; }


$oid_list = [
    // Path 1
    6832 => " - Ch 1",
    6833 => " - Ch 2",
    6834 => " - Ch 3",
    6835 => " - Ch 4",
    6836 => " - Ch 5",
    6837 => " - Ch 6",
    6838 => " - Ch 7",
    6839 => " - Ch 8",

    // Path 2
    6848 => " - Ch 1",
    6849 => " - Ch 2",
    6850 => " - Ch 3",
    6851 => " - Ch 4",
    6852 => " - Ch 5",
    6853 => " - Ch 6",
    6854 => " - Ch 7",
    6855 => " - Ch 8",

    // Path 3
    6864 => " - Ch 1",
    6865 => " - Ch 2",
    6866 => " - Ch 3",
    6867 => " - Ch 4",
    6868 => " - Ch 5",
    6869 => " - Ch 6",
    6870 => " - Ch 7",
    6871 => " - Ch 8",

    // Path 4
    6880 => " - Ch 1",
    6881 => " - Ch 2",
    6882 => " - Ch 3",
    6883 => " - Ch 4",
    6884 => " - Ch 5",
    6885 => " - Ch 6",
    6886 => " - Ch 7",
    6887 => " - Ch 8",
];

// ------------------------------
// AUDIO SOURCE TABLE (friendly names)
// ------------------------------
$sources = [
    // MADI (128?191)
    128=>"MADI RX 1", 129=>"MADI RX 2", 130=>"MADI RX 3", 131=>"MADI RX 4",
    132=>"MADI RX 5", 133=>"MADI RX 6", 134=>"MADI RX 7", 135=>"MADI RX 8",
    136=>"MADI RX 9", 137=>"MADI RX 10", 138=>"MADI RX 11", 139=>"MADI RX 12",
    140=>"MADI RX 13", 141=>"MADI RX 14", 142=>"MADI RX 15", 143=>"MADI RX 16",
    144=>"MADI RX 17", 145=>"MADI RX 18", 146=>"MADI RX 19", 147=>"MADI RX 20",
    148=>"MADI RX 21", 149=>"MADI RX 22", 150=>"MADI RX 23", 151=>"MADI RX 24",
    152=>"MADI RX 25", 153=>"MADI RX 26", 154=>"MADI RX 27", 155=>"MADI RX 28",
    156=>"MADI RX 29", 157=>"MADI RX 30", 158=>"MADI RX 31", 159=>"MADI RX 32",

    // Buses
    0=>"Path 1 EB Ch 1", 1=>"Path 1 EB Ch 2", 2=>"Path 1 EB Ch 3",
    3=>"Path 1 EB Ch 4", 4=>"Path 1 EB Ch 5", 5=>"Path 1 EB Ch 6",
    6=>"Path 1 EB Ch 7", 7=>"Path 1 EB Ch 8", 8=>"Path 1 EB Ch 9",
    9=>"Path 1 EB Ch 10", 10=>"Path 1 EB Ch 11", 11=>"Path 1 EB Ch 12",
    12=>"Path 1 EB Ch 13", 13=>"Path 1 EB Ch 14", 14=>"Path 1 EB Ch 15",
    15=>"Path 1 EB Ch 16",

    16=>"Path 2 EB Ch 1", 17=>"Path 2 EB Ch 2", 18=>"Path 2 EB Ch 3",
    19=>"Path 2 EB Ch 4", 20=>"Path 2 EB Ch 5", 21=>"Path 2 EB Ch 6",
    22=>"Path 2 EB Ch 7", 23=>"Path 2 EB Ch 8", 24=>"Path 2 EB Ch 9",
    25=>"Path 2 EB Ch 10", 26=>"Path 2 EB Ch 11", 27=>"Path 2 EB Ch 12",
    28=>"Path 2 EB Ch 13", 29=>"Path 2 EB Ch 14", 30=>"Path 2 EB Ch 15",
    31=>"Path 2 EB Ch 16",

    32=>"Path 3 EB Ch 1", 33=>"Path 3 EB Ch 2", 34=>"Path 3 EB Ch 3",
    35=>"Path 3 EB Ch 4", 36=>"Path 3 EB Ch 5", 37=>"Path 3 EB Ch 6",
    38=>"Path 3 EB Ch 7", 39=>"Path 3 EB Ch 8", 40=>"Path 3 EB Ch 9",
    41=>"Path 3 EB Ch 10", 42=>"Path 3 EB Ch 11", 43=>"Path 3 EB Ch 12",
    44=>"Path 3 EB Ch 13", 45=>"Path 3 EB Ch 14", 46=>"Path 3 EB Ch 15",
    47=>"Path 3 EB Ch 16",

    48=>"Path 4 EB Ch 1", 49=>"Path 4 EB Ch 2", 50=>"Path 4 EB Ch 3",
    51=>"Path 4 EB Ch 4", 52=>"Path 4 EB Ch 5", 53=>"Path 4 EB Ch 6",
    54=>"Path 4 EB Ch 7", 55=>"Path 4 EB Ch 8", 56=>"Path 4 EB Ch 9",
    57=>"Path 4 EB Ch 10", 58=>"Path 4 EB Ch 11", 59=>"Path 4 EB Ch 12",
    60=>"Path 4 EB Ch 13", 61=>"Path 4 EB Ch 14", 62=>"Path 4 EB Ch 15",
    63=>"Path 4 EB Ch 16",



    4080=>"Silence",
];



// ------------------------------
// HANDLE FORM SUBMIT
// ------------------------------
$message = "";

// Only handle POST if OIDs are submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['oids'])) {

    // Check if user has permission to save (>= 2)
if ((int)$_SESSION['user_permissions'] < 2) {
    // Detect AJAX request and respond with JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient permissions',
            'redirect' => "/9905-mpx/?id={$id}"
        ]);
        exit;
    } else {
        // normal form submission
        header("Location: /9905-mpx/?id=".$id);
        exit;
    }
}

    $allSuccess = true; // track if all updates succeed
    $failedOIDs = [];



foreach ($_POST['oids'] as $oid => $value) {
    $oid = intval($oid);
    $value = intval($value);
    $url = "http://{$ip}:9002/setOid?oid={$oid}&value={$value}";

    // Create a context with a timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 1 // Timeout in seconds
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $allSuccess = false;
        $failedOIDs[] = $oid;
    }
}

    // Prepare message
    if ($allSuccess) {
        // After all OIDs are successfully updated
echo json_encode([
    'success' => true,
    'message' => 'Changes saved successfully.' // optional, can be shown in notification
]);
exit;

    } else {
        $message = "Failed to update OIDs: " . implode(", ", $failedOIDs);
    }

    // Redirect back with message
    header("Location: /9905-mpx/?id=".$id."&message=".urlencode($message));
    exit;
}
// ------------------------------
// GET CURRENT VALUES
// ------------------------------
$current_values = [];
$context = stream_context_create([
    'http' => [
        'timeout' => 2 // 2 seconds timeout
    ]
]);

foreach ($oid_list as $oid => $name) {
    $json = @file_get_contents("http://{$ip}:9002/getOid?oid={$oid}", false, $context);
    if ($json) {
        $data = json_decode($json, true);
        $current_values[$oid] = $data["data_updates"]["oids"][0]["data_value"] ?? null;
    }
}

function getOidName($ip, $oid) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 1 // 2 seconds timeout
        ]
    ]);

    $json = @file_get_contents("http://{$ip}:9002/getOid?oid={$oid}", false, $context);
    if ($json) {
        $data = json_decode($json, true);
        $val = $data["data_updates"]["oids"][0]["data_value"] ?? null;
        return $val; // map numeric value to friendly name
    }
    return "Unknown";
}


// Example usage
$Path1Name = getOidName($ip, 4866);
$Path2Name = getOidName($ip, 4867);
$Path3Name = getOidName($ip, 4868);
$Path4Name = getOidName($ip, 4869);

$pathNames = [
    6832 => $Path1Name, 6833 => $Path1Name, 6834 => $Path1Name, 6835 => $Path1Name,
    6836 => $Path1Name, 6837 => $Path1Name, 6838 => $Path1Name, 6839 => $Path1Name,

    6848 => $Path2Name, 6849 => $Path2Name, 6850 => $Path2Name, 6851 => $Path2Name,
    6852 => $Path2Name, 6853 => $Path2Name, 6854 => $Path2Name, 6855 => $Path2Name,

    6864 => $Path3Name, 6865 => $Path3Name, 6866 => $Path3Name, 6867 => $Path3Name,
    6868 => $Path3Name, 6869 => $Path3Name, 6870 => $Path3Name, 6871 => $Path3Name,

    6880 => $Path4Name, 6881 => $Path4Name, 6882 => $Path4Name, 6883 => $Path4Name,
    6884 => $Path4Name, 6885 => $Path4Name, 6886 => $Path4Name, 6887 => $Path4Name,
];




?>
<?php include "../header.php"; ?>
<!DOCTYPE html>
<html>
<head>
<title>9905-MPX Embedding</title>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<body>
<div id="notification" style="
    display: none;
    position: fixed;
    top: 100px;
    left: 50%;
    transform: translateX(-50%);
    background: #ff4d4d;
    color: #fff;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    z-index: 9999;
"></div>

<div class="container">

<h2><?php echo $x32name; ?> Embeding Control</h2>
<p>Framesync : <strong><?php echo $ip; ?></strong></p>




<a href="" ><button id="reloadButton" >Refresh</button></a>
<button id="takeButton">Save Routing</button>

<table id="routerTable">
    <tbody>
    <?php
    $i = 0;
    foreach ($oid_list as $oid => $name):
        if ($i % 8 == 0) echo "<tr>";
    ?>
    <td>
        <strong><?php echo htmlspecialchars($pathNames[$oid] ?? $name); echo $name;?></strong>

        <div class="input-wrapper">
            <input class="search-box" data-oid="<?php echo $oid; ?>" autocomplete="off"
                   value="<?php echo htmlspecialchars($sources[$current_values[$oid]] ?? ''); ?>">
            <div class="suggestions" style="display:none"></div>
        </div>
    </td>
    <?php
        $i++;
        if ($i % 8 == 0) echo "</tr>";
    endforeach;
    if ($i % 8 != 0) echo "</tr>";
    ?>
    </tbody>
</table>
  </div>
<script>
// --- PHP-supplied sources and current values ---
let sources = <?php echo json_encode($sources); ?>;
let currentValues = <?php echo json_encode($current_values); ?>;

// --- Utility function for selecting a suggestion ---
function selectItem(inputEl, key){
    inputEl.value = sources[key];
    inputEl.dataset.selectedKey = key;

    // Highlight green if changed
    const oid = parseInt(inputEl.dataset.oid);
    if (currentValues[oid] != key) {
        inputEl.classList.add("pending-change");
    } else {
        inputEl.classList.remove("pending-change");
    }

    inputEl.parentElement.querySelector(".suggestions").style.display = "none";
    updateTakeButtonVisibility();
}

// --- Show/Hide take button based on pending changes ---
function updateTakeButtonVisibility() {
    const anyChanges = Array.from(document.querySelectorAll('.search-box'))
        .some(input => input.classList.contains("pending-change"));
    document.getElementById("takeButton").style.display = anyChanges ? "inline-block" : "none";
}

// --- Initialize autocomplete on an input ---
function initAutocomplete(inputEl){
    const box = inputEl.parentElement;
    const sug = box.querySelector(".suggestions");
    let activeIndex = -1;

    function updateSuggestions(){
        const v = inputEl.value.toLowerCase();
        const filtered = Object.entries(sources)
            .map(([k,label])=>({key:k,label}))
            .filter(x=>x.label.toLowerCase().includes(v));
        sug.innerHTML = ""; activeIndex = -1;

        if(!filtered.length){sug.style.display="none"; return;}
        filtered.forEach(f=>{
            const div = document.createElement("div");
            div.className="suggestion-item"; div.textContent=f.label; div.dataset.key=f.key;
            div.onclick = ()=> selectItem(inputEl,f.key);
            sug.appendChild(div);
        });
        sug.style.display = "block";
    }

    inputEl.addEventListener("input", updateSuggestions);
    inputEl.addEventListener("focus", ()=>{ if(!inputEl.value) updateSuggestions(); });

    inputEl.addEventListener("keydown", e=>{
        const items = sug.querySelectorAll(".suggestion-item");
        if(!items.length) return;

        if(e.key==="ArrowDown"){ e.preventDefault(); activeIndex=(activeIndex+1)%items.length; updateActive(items); }
        else if(e.key==="ArrowUp"){ e.preventDefault(); activeIndex=(activeIndex-1+items.length)%items.length; updateActive(items); }
        else if(e.key==="Enter" || e.key==="Tab"){
            e.preventDefault();
            if(activeIndex<0) activeIndex=0;
            selectItem(inputEl, items[activeIndex].dataset.key);
        }
    });

    document.addEventListener("click", e=>{ if(!box.contains(e.target)) sug.style.display="none"; });

    function updateActive(items){
        items.forEach(x=>x.classList.remove("suggestion-active"));
        if(activeIndex>=0) items[activeIndex].classList.add("suggestion-active");
    }
}

// --- Initialize all inputs ---
document.querySelectorAll('.search-box').forEach(input=>{
    const oid = parseInt(input.dataset.oid);
    if(currentValues[oid] !== undefined){
        const val = currentValues[oid];
        input.value = sources[val] || '';
        input.dataset.selectedKey = val;
    }
    initAutocomplete(input);
});

<?php if($madisorce != 0){ ?>
// --- Fetch live MADI/Ultranet names if $madisorce is not 0 ---
fetch("/x32/getallnames.php?id=<?php echo $madisorce; ?>")
    .then(res=>res.json())
    .then(data=>{
        if(!Array.isArray(data)) return;
        data.forEach((item,i)=>{
            sources[128 + i] = item.name || `Ultranet RX ${i+1}`;
        });

        // update the input values with the new names
        document.querySelectorAll('.search-box').forEach(input=>{
            const oid = parseInt(input.dataset.oid);
            const val = currentValues[oid];
            input.value = sources[val] || '';
        });
    })
    .catch(err=>console.error("Ultranet fetch failed:", err));
<?php } ?>

// --- Handle manual input changes ---
document.addEventListener("input", e=>{
    if(e.target.classList.contains("search-box")){
        const oid = parseInt(e.target.dataset.oid);
        const selectedKey = parseInt(e.target.dataset.selectedKey);
        if (currentValues[oid] != selectedKey) {
            e.target.classList.add("pending-change");
        } else {
            e.target.classList.remove("pending-change");
        }
        updateTakeButtonVisibility();
    }
});

// --- Handle Take button click ---
document.getElementById("takeButton").addEventListener("click", async () => {
    const oids = {};
    document.querySelectorAll('.search-box.pending-change').forEach(input => {
        if (input.dataset.selectedKey) oids[input.dataset.oid] = input.dataset.selectedKey;
    });

    if (Object.keys(oids).length === 0) {
        alert("No changes to save.");
        return;
    }

    const params = new URLSearchParams();
    for (const [oid, val] of Object.entries(oids)) {
        params.append(`oids[${oid}]`, val);
    }

    try {
        const res = await fetch("", {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest' // identify AJAX request
            },
            body: params.toString()
        });

        const data = await res.json();

        if (data.success === false && data.redirect) {
            window.location.href = data.redirect;
            return;
        }

        // If no errors, assume success
        for (const oid in oids) {
            currentValues[oid] = oids[oid];
            const input = document.querySelector(`.search-box[data-oid="${oid}"]`);
            if (input) input.classList.remove("pending-change");
        }
        updateTakeButtonVisibility();
        


    } catch (err) {
        console.error("Failed to save OIDs:", err);
        alert("Failed to save changes. See console for details.");
    }
});


</script>





</body>
</html>