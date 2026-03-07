<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}

if (!validateUserSession($conn, 3)) {
    showAccessDenied();
    exit;
}

$notification = "";

$editMode = false;
$editID = $_GET["id"] ?? null;

$existing = [
    "name" => "",
    "ip" => "",
    "pluginID" => ""
];

if ($editID) {
    $editMode = true;

    $stmt = $conn->prepare("SELECT name, ip, pluginID,madisorce FROM devices WHERE id = ?");
    $stmt->bind_param("i", $editID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $existing = $result->fetch_assoc();
    } else {
        $notification = "Device not found.";
        $editMode = false;
    }
}

// --- Submit Logic ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? "");
    $ip = trim($_POST["ip"] ?? "");
    $device = trim($_POST["device"] ?? "");
    $madisorce = trim($_POST["madisorce"] ?? "0");

    if ($editMode) {
        // UPDATE existing device
        $stmt = $conn->prepare("UPDATE `devices` SET name = ?, ip = ?, pluginID = ?, madisorce=? WHERE id = ?");
        $stmt->bind_param("ssisi", $name, $ip, $device,$madisorce, $editID);
        $successMsg = "Device updated successfully.";
                header("Location: equitment.php?id=".$editID); 
    } else {
        // INSERT new device
        $stmt = $conn->prepare("INSERT INTO `devices` (name, ip, pluginID, madisorce) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $name, $ip, $device ,$madisorce);
        
        $successMsg = "Device added successfully.";

    }

    if ($stmt->execute()) {
        $notification = $successMsg;
    } else {
        $notification = "Error: " . htmlspecialchars($stmt->error);
    }
}
?>
<?php include "../header.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $editMode ? "Edit Device" : "Add Device"; ?></title>

<style>
body {
    background-color: #232323;
    color: #FFF;
}
</style>
</head>
<body>

<div class="container">
    <div class="py-5 text-center">
        <h2><?php echo $editMode ? "Edit Device" : "Add a New Device"; ?></h2>
    </div>

    <?php if(!empty($notification)): ?>
    <div class="alert alert-dismissible <?php echo (strpos($notification, 'Error') !== false ? 'alert-danger' : 'alert-success'); ?>">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo $notification; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="name">Device Name</label>
            <input type="text" class="form-control" id="name" name="name"
                   value="<?php echo htmlspecialchars($existing['name']); ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="ip">Device IP</label>
            <input type="text" class="form-control" id="ip" name="ip"
                   value="<?php echo htmlspecialchars($existing['ip']); ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="device">Select Device Type</label>
            <select class="form-control" id="device" name="device" required>
                <?php      
                $query = "SELECT * FROM `deviceplugin`";
                if ($result = $conn->query($query)) {
                    while ($row = $result->fetch_assoc()) { 
                        $selected = ($existing['pluginID'] == $row['id']) ? "selected" : "";
                        echo '<option value="'.$row['id'].'" '.$selected.'>'.$row['pluginName'].'</option>';
                    }
                }
                ?>    
            </select>
        </div>
        <?php  if (($existing['pluginID'] == 3) || (!$editMode)) { ?>  
        <div class="form-group">
            <label for="device">Madi Sorce (Embeding Devices Only)</label>
            <select class="form-control" id="madisorce" name="madisorce" required>
                <?php      
                $query = "SELECT * FROM `devices` WHERE pluginID = 1";
                echo '<option value="0"></option>';
                if ($result = $conn->query($query)) {
                    while ($row = $result->fetch_assoc()) { 
                        $selected = ($existing['madisorce'] == $row['id']) ? "selected" : "";
                        echo '<option value="'.$row['id'].'" '.$selected.'>'.$row['name'].'</option>';
                    }
                }
                ?>    
            </select>
        </div>
         <?php } ?> 

        <button type="submit" class="btn btn-primary">
            <?php echo $editMode ? "Save Changes" : "Add"; ?>
        </button>
        <a class="btn btn-primary" href="/device/">Back</a>
    </form>
</div>

</body>
</html>
