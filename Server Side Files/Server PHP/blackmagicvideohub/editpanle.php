<?php
include "../config.php";
session_start();

// --- Permissions ---
if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 3)) { showAccessDenied(); exit; }

$notification = "";
$editMode = false;
$editID = $_GET["id"] ?? null;
$returnid= $_GET["return"] ?? null;

$existing = [
    "panleName" => "",
    "deviceID" => "",
    "allowedUsers" => "",
    "sorces" => "",
    "destnations" => ""
];

// --- Fetch existing panel if editing ---
if ($editID) {
    $editMode = true;
    $stmt = $conn->prepare("SELECT panleName, deviceID, allowedUsers, sorces, destnations FROM routerpanle WHERE id = ?");
    $stmt->bind_param("i", $editID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $existing = $result->fetch_assoc();
    } else {
        $notification = "Panel not found.";
        $editMode = false;
    }
}

// --- Fetch users for allowedUsers multi-select ---
$users = [];
$userResult = $conn->query("SELECT id, UserEmail FROM `Admin Users` ORDER BY UserEmail ASC");
if ($userResult) {
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// --- Fetch devices for dropdown ---
$devices = $conn->query("SELECT id, name FROM devices WHERE pluginID=2 ORDER BY name ASC");

// --- Handle AJAX request to fetch inputs/outputs ---
if (isset($_GET['action']) && $_GET['action'] === 'get_io' && isset($_GET['deviceID'])) {
    $deviceID = intval($_GET['deviceID']);
    $stmt = $conn->prepare("SELECT ip FROM devices WHERE id=? AND pluginID=2");
    $stmt->bind_param("i", $deviceID);
    $stmt->execute();
    $stmt->bind_result($host);
    if ($stmt->fetch()) {
        echo json_encode(vh_get_full_status($host, 9990, 2.0));
    } else {
        echo json_encode(["error" => "Device not found"]);
    }
    exit;
}

// --- Handle form submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $panleName = trim($_POST["panleName"] ?? "");
    $deviceID = intval($_POST["deviceID"] ?? 0);
    $allowedUsers = $_POST["allowedUsers"] ?? [];
    $allowedInputs  = $_POST["allowedInputs"] ?? [];
    $allowedOutputs = $_POST["allowedOutputs"] ?? [];

    $allowedUsersJSON  = json_encode(array_map('intval', $allowedUsers));
    $sorcesJSON        = json_encode(array_map('intval', $allowedInputs));
    $destnationsJSON   = json_encode(array_map('intval', $allowedOutputs));

    if ($editMode) {
        $stmt = $conn->prepare("UPDATE routerpanle SET panleName=?, deviceID=?, allowedUsers=?, sorces=?, destnations=? WHERE id=?");
        $stmt->bind_param("sisssi", $panleName, $deviceID, $allowedUsersJSON, $sorcesJSON, $destnationsJSON, $editID);
        $successMsg = "Panel updated successfully.";
    } else {
        $stmt = $conn->prepare("INSERT INTO routerpanle (panleName, deviceID, allowedUsers, sorces, destnations) VALUES (?,?, ?, ?, ?)");
        $stmt->bind_param("sisss", $panleName, $deviceID, $allowedUsersJSON, $sorcesJSON, $destnationsJSON);
        header("Location: managevirtialpanles.php?id=".$returnid);
    }

    if ($stmt->execute()) {
        $notification = $successMsg;
        $existing['panleName'] = $panleName;
        $existing['deviceID']  = $deviceID;
        $existing['allowedUsers'] = $allowedUsersJSON;
        $existing['sorces'] = $sorcesJSON;
        $existing['destnations'] = $destnationsJSON;
        $editMode = true;
    } else {
        $notification = "Error: " . htmlspecialchars($stmt->error);
    }
}

include "../header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $editMode ? "Edit Videohub Panel" : "Add Videohub Panel" ?></title>
<style>
body { background-color:#232323; color:#FFF; font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
.container { max-width:600px; margin:40px auto; padding:20px; background:#2c2c2c; border-radius:10px; }
h2 { text-align:center; margin-bottom:20px; }
.form-group { margin-bottom:15px; }
label { display:block; margin-bottom:5px; }
input, select, button { width:100%; padding:10px; border-radius:5px; border:1px solid #444; background:#1a1a1a; color:#fff; }
select[multiple] { height:150px; }
button { background-color:#1abc9c; border:none; cursor:pointer; margin-top:10px; }
button:hover { background-color:#16a085; }
.alert { padding:10px; margin-bottom:15px; border-radius:5px; }
.alert-success { background-color:#27ae60; }
.alert-danger { background-color:#c0392b; }
</style>
</head>
<body>

<div class="container">
<h2><?= $editMode ? "Edit Videohub Panel" : "Add a New Videohub Panel" ?></h2>

<?php if(!empty($notification)): ?>
<div class="alert <?= strpos($notification,'Error')!==false?'alert-danger':'alert-success' ?>"><?= $notification ?></div>
<?php endif; ?>

<form method="post" id="panelForm">
    <div class="form-group">
        <label for="panleName">Panel Name</label>
        <input type="text" id="panleName" name="panleName" value="<?= htmlspecialchars($existing['panleName']) ?>" required>
    </div>

    <div class="form-group">
        <label for="deviceID">Select Videohub Device</label>
        <select id="deviceID" name="deviceID" required>
            <?php while($d = $devices->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>" <?= ($existing['deviceID']==$d['id'])?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="allowedUsers">Allowed Users (Ctrl+Click for multiple)</label>
        <select id="allowedUsers" name="allowedUsers[]" multiple>
            <?php
            $currentAllowed = json_decode($existing['allowedUsers'],true)??[];
            foreach($users as $user):
                $selected = in_array($user['id'],$currentAllowed)?'selected':'';
            ?>
                <option value="<?= $user['id'] ?>" <?= $selected ?>><?= htmlspecialchars($user['UserEmail']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="allowedInputs">Allowed Inputs (Ctrl+Click for multiple)</label>
        <select id="allowedInputs" name="allowedInputs[]" multiple></select>
    </div>

    <div class="form-group">
        <label for="allowedOutputs">Allowed Outputs (Ctrl+Click for multiple)</label>
        <select id="allowedOutputs" name="allowedOutputs[]" multiple></select>
    </div>
<div class="form-group" style="display: flex; gap: 10px; margin-top: 20px;">
    <!-- Submit button -->
    <button type="submit" class="btn btn-primary" style="flex: 1;">
        <?php echo $editMode ? "Save Changes" : "Add"; ?>
    </button>

    <!-- Back button -->
    <button type="button" class="btn btn-primary" style="flex: 1;" onclick="window.location.href='managevirtialpanles.php?id=<?php echo $returnid?>';">
        Back
    </button>
</div>

</form>

</div>

<script>
// Fill input/output multi-selects dynamically
async function fetchIO(deviceID){
    const res = await fetch("?action=get_io&deviceID="+encodeURIComponent(deviceID));
    const data = await res.json();
    const inputSel = document.getElementById("allowedInputs");
    const outputSel = document.getElementById("allowedOutputs");
    inputSel.innerHTML = "";
    outputSel.innerHTML = "";

    // --- Already selected inputs/outputs from DB ---
    const existingInputs  = <?= json_encode(json_decode($existing['sorces'], true) ?? []) ?>;
    const existingOutputs = <?= json_encode(json_decode($existing['destnations'], true) ?? []) ?>;

    if(data.error){
        inputSel.innerHTML = "<option disabled>"+data.error+"</option>";
        outputSel.innerHTML = "<option disabled>"+data.error+"</option>";
        return;
    }

    // Populate inputs
    for(const [id,label] of Object.entries(data.input_labels)){
        const opt = document.createElement("option");
        opt.value = id;
        opt.text = label;
        if(existingInputs.includes(parseInt(id))) opt.selected = true; // <-- Pre-select existing
        inputSel.add(opt);
    }

    // Populate outputs
    for(const [id,label] of Object.entries(data.output_labels)){
        const opt = document.createElement("option");
        opt.value = id;
        opt.text = label;
        if(existingOutputs.includes(parseInt(id))) opt.selected = true; // <-- Pre-select existing
        outputSel.add(opt);
    }
}

document.getElementById("deviceID").addEventListener("change", function(){
    fetchIO(this.value);
});

// Fetch initially
if(document.getElementById("deviceID").value) fetchIO(document.getElementById("deviceID").value);
</script>

<?php
// ----------------- Backend functions -----------------
function vh_get_full_status($host, $port, $timeout){
    $fp=@stream_socket_client("tcp://$host:$port",$e1,$e2,$timeout);
    if(!$fp) return ["error"=>"connect failed"];
    stream_set_blocking($fp,true);
    stream_set_timeout($fp,$timeout);

    $raw=""; $start=microtime(true);
    while(true){
        $line=fgets($fp);
        if($line!==false) $raw.=$line;
        $meta=stream_get_meta_data($fp);
        if($meta['timed_out'] || (microtime(true)-$start)>($timeout+0.2)) break;
    }
    fclose($fp);

    $blocks=[]; $lines=preg_split("/\r?\n/",$raw); $block="";
    foreach($lines as $line){
        if(preg_match("/^([A-Z0-9 _]+):$/",$line,$m)) $block=$m[1];
        elseif($block && trim($line)!="") $blocks[$block][]=$line;
    }

    $inputs=[]; $outputs=[]; $routing=[];
    if(!empty($blocks["INPUT LABELS"])) foreach($blocks["INPUT LABELS"] as $ln) if(preg_match("/(\d+)\s+(.*)/",$ln,$m)) $inputs[$m[1]]=$m[2];
    if(!empty($blocks["OUTPUT LABELS"])) foreach($blocks["OUTPUT LABELS"] as $ln) if(preg_match("/(\d+)\s+(.*)/",$ln,$m)) $outputs[$m[1]]=$m[2];
    if(!empty($blocks["VIDEO OUTPUT ROUTING"])) foreach($blocks["VIDEO OUTPUT ROUTING"] as $ln) if(preg_match("/(\d+)\s+(\d+)/",$ln,$m)) $routing[$m[1]]=$m[2];

    return ["input_labels"=>$inputs,"output_labels"=>$outputs,"video_output_routing"=>$routing];
}
?>
</body>
</html>
