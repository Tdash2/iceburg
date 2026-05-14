<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}

if (!validateUserSession($conn, 4)) {
    showAccessDenied();
    exit;
}

$notification = "";

$editMode = false;
$editID = $_GET["id"] ?? null;

$existing = [
    "name" => "",
    "ip" => "",
    "pluginID" => "",
    "username" => "",
    "password" => "",
    "madisorce" => ""
];

if ($editID) {
    $editMode = true;

    $stmt = $conn->prepare("
        SELECT name, ip, pluginID, madisorce, username, password
        FROM devices
        WHERE id = ?
    ");

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

    $name       = trim($_POST["name"] ?? "");
    $ip         = trim($_POST["ip"] ?? "");
    $device     = trim($_POST["device"] ?? "");
    $madisorce  = trim($_POST["madisorce"] ?? "0");
    $username   = trim($_POST["username"] ?? "");
    $password   = trim($_POST["password"] ?? "");

    if ($editMode) {

        // UPDATE existing device
        $stmt = $conn->prepare("
            UPDATE devices
            SET
                name = ?,
                ip = ?,
                pluginID = ?,
                madisorce = ?,
                username = ?,
                password = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssiissi",
            $name,
            $ip,
            $device,
            $madisorce,
            $username,
            $password,
            $editID
        );

        $successMsg = "Device updated successfully.";

    } else {

        // INSERT new device
        $stmt = $conn->prepare("
            INSERT INTO devices
            (
                name,
                ip,
                pluginID,
                madisorce,
                username,
                password
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssiiss",
            $name,
            $ip,
            $device,
            $madisorce,
            $username,
            $password
        );

        $successMsg = "Device added successfully.";
    }

    if ($stmt->execute()) {

        if ($editMode) {
            header("Location: equitment.php?id=" . $editID);
            exit;
        }

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

<title>
    <?php echo $editMode ? "Edit Device" : "Add Device"; ?>
</title>

<style>
body {
    background-color: #232323;
    color: #FFF;
}

.container {
    max-width: 700px;
}

.form-control {
    background-color: #2f2f2f;
    color: white;
    border: 1px solid #555;
}

.form-control:focus {
    background-color: #2f2f2f;
    color: white;
}

label {
    font-weight: bold;
}

.btn {
    margin-top: 10px;
}
</style>

</head>
<body>

<div class="container">

    <div class="py-5 text-center">
        <h2>
            <?php echo $editMode ? "Edit Device" : "Add a New Device"; ?>
        </h2>
    </div>

    <?php if (!empty($notification)): ?>

        <div class="alert alert-dismissible <?php echo (strpos($notification, 'Error') !== false ? 'alert-danger' : 'alert-success'); ?>">

            <button type="button" class="close" data-dismiss="alert">
                &times;
            </button>

            <?php echo $notification; ?>

        </div>

    <?php endif; ?>

    <form method="post" action="">

        <!-- Device Name -->
        <div class="form-group">
            <label for="name">Device Name</label>

            <input
                type="text"
                class="form-control"
                id="name"
                name="name"
                value="<?php echo htmlspecialchars($existing['name']); ?>"
                required
            >
        </div>

        <!-- Device IP -->
        <div class="form-group">
            <label for="ip">Device IP</label>

            <input
                type="text"
                class="form-control"
                id="ip"
                name="ip"
                value="<?php echo htmlspecialchars($existing['ip']); ?>"
                required
            >
        </div>

        <!-- Username -->
        <div class="form-group">
            <label for="username">Username</label>

            <input
                type="text"
                class="form-control"
                id="username"
                name="username"
                value="<?php echo htmlspecialchars($existing['username']); ?>"
            >
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password">Password</label>

            <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                value="<?php echo htmlspecialchars($existing['password']); ?>"
            >
        </div>

        <!-- Device Type -->
        <div class="form-group">

            <label for="device">Select Device Type</label>

            <select
                class="form-control"
                id="device"
                name="device"
                required
            >

                <?php

                $query = "SELECT * FROM deviceplugin";

                if ($result = $conn->query($query)) {

                    while ($row = $result->fetch_assoc()) {

                        $selected = ($existing['pluginID'] == $row['id'])
                            ? "selected"
                            : "";

                        echo '
                            <option value="' . $row['id'] . '" ' . $selected . '>
                                ' . htmlspecialchars($row['pluginName']) . '
                            </option>
                        ';
                    }
                }

                ?>

            </select>

        </div>

        <!-- MADI Source -->
        <?php if (in_array($existing['pluginID'], [11, 3]) || !$editMode) { ?>

        <div class="form-group">

            <label for="madisorce">
                Madi Source (Embedding Devices Only)
            </label>

            <select
                class="form-control"
                id="madisorce"
                name="madisorce"
                required
            >

                <option value="0"></option>

                <?php

                $query = "SELECT * FROM devices WHERE pluginID = 1";

                if ($result = $conn->query($query)) {

                    while ($row = $result->fetch_assoc()) {

                        $selected = ($existing['madisorce'] == $row['id'])
                            ? "selected"
                            : "";

                        echo '
                            <option value="' . $row['id'] . '" ' . $selected . '>
                                ' . htmlspecialchars($row['name']) . '
                            </option>
                        ';
                    }
                }

                ?>

            </select>

        </div>

        <?php } ?>

        <!-- Buttons -->
        <button type="submit" class="btn btn-primary">

            <?php echo $editMode ? "Save Changes" : "Add"; ?>

        </button>

        <a class="btn btn-secondary" href="/device/">
            Back
        </a>

    </form>

</div>

</body>
</html>