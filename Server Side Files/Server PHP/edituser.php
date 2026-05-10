<?php
include "config.php";
session_start();

if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}

if (!validateUserSession($conn, 5)) {
    showAccessDenied();
    exit;
}

$notification = "";

if (isset($_GET['saved'])) {
    $notification = "User updated successfully.";
}

$user_id = intval($_GET['id'] ?? 0);

if ($user_id <= 0) {
    die("Invalid user ID.");
}

/*
|--------------------------------------------------------------------------
| SAVE FIRST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $new_username = trim($_POST["username"] ?? "");
    $new_password = trim($_POST["password"] ?? "");
    $confirm_password = trim($_POST["confirm_password"] ?? "");
    $new_permissions = intval($_POST["permissions"] ?? 1);

    $newAllowedPlugins = $_POST["allowedPlugins"] ?? [];
    $newAllowedPluginsJson = json_encode($newAllowedPlugins);

    $newUseCustomNav = isset($_POST["useCoustomNav1"]) ? 1 : 0;

    /*
    |--------------------------------------------------------------------------
    | NAV LIST BUILD
    |--------------------------------------------------------------------------
    */

    $newNavElements = [];

    $navItems = json_decode($_POST["nav_json"] ?? "[]", true);

    if (is_array($navItems)) {

        foreach ($navItems as $item) {

            if (!isset($item['device_id'], $item['path'])) {
                continue;
            }

            $newNavElements[] = [
                "device_id" => strval($item['device_id']),
                "label" => $item['label'] ?? $item['name'] ?? '',
                "name" => $item['name'] ?? $item['label'] ?? '',
                "path" => $item['path']
            ];
        }
    }

    $newNavElementsJson = json_encode($newNavElements);

    /*
    |--------------------------------------------------------------------------
    | PASSWORD
    |--------------------------------------------------------------------------
    */

    if (!empty($new_password) && $new_password !== $confirm_password) {

        $notification = "Error: Passwords do not match.";

    } else {

        if (!empty($new_password)) {

            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                UPDATE `Admin Users`
                SET
                    UserEmail=?,
                    UserPassword=?,
                    UserPermissions=?,
                    allowedPlugins=?,
                    useCoustomNav=?,
                    NavElements=?
                WHERE id=?
            ");

            $stmt->bind_param(
                "ssisisi",
                $new_username,
                $hashed,
                $new_permissions,
                $newAllowedPluginsJson,
                $newUseCustomNav,
                $newNavElementsJson,
                $user_id
            );

        } else {

            $stmt = $conn->prepare("
                UPDATE `Admin Users`
                SET
                    UserEmail=?,
                    UserPermissions=?,
                    allowedPlugins=?,
                    useCoustomNav=?,
                    NavElements=?
                WHERE id=?
            ");

            $stmt->bind_param(
                "sisisi",
                $new_username,
                $new_permissions,
                $newAllowedPluginsJson,
                $newUseCustomNav,
                $newNavElementsJson,
                $user_id
            );
        }

        if ($stmt->execute()) {

            $stmt->close();

            /*
            |--------------------------------------------------------------------------
            | REDIRECT TO RELOAD FRESH DATA
            |--------------------------------------------------------------------------
            */

            header("Location: edituser.php?id=" . $user_id . "&saved=1");
            exit;

        } else {

            $notification = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| LOAD USER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        UserEmail,
        UserPermissions,
        allowedPlugins,
        useCoustomNav,
        NavElements
    FROM `Admin Users`
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$stmt->bind_result(
    $username,
    $permissions,
    $allowedPluginsJson,
    $useCoustomNav1,
    $NavElementsJson
);

if (!$stmt->fetch()) {
    die("User not found.");
}

$stmt->close();

$currentAllowedPlugins = json_decode($allowedPluginsJson, true);
if (!is_array($currentAllowedPlugins)) {
    $currentAllowedPlugins = [];
}

$currentNavElements = json_decode($NavElementsJson, true);
if (!is_array($currentNavElements)) {
    $currentNavElements = [];
}

/*
|--------------------------------------------------------------------------
| DEVICES
|--------------------------------------------------------------------------
*/

$pluginOptions = [];

$result = $conn->query("
    SELECT id, name, pluginID
    FROM devices
    ORDER BY id ASC
");

while ($row = $result->fetch_assoc()) {
    $pluginOptions[] = $row;
}
?>

<?php include "header.php"; ?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Edit User</title>

<style>
body { background:#232323; color:#fff; }

.box {
    background:#2e2e2e;
    padding:15px;
    border-radius:8px;
    margin-bottom:15px;
}

.nav-item {
    padding:10px;
    border:1px solid #444;
    margin-bottom:10px;
    border-radius:6px;
    background:#1f1f1f;
}

button { margin-top:10px; }
</style>

</head>
<body>

<div class="container">

<h2>Edit User</h2>

<?php if ($notification): ?>
<div class="alert alert-info"><?= $notification ?></div>
<?php endif; ?>

<form method="post" id="mainForm">
<div class="form-group">
<label for="username">Email / Username</label>
<input class="form-control" name="username" value="<?= htmlspecialchars($username) ?>">
</div>
<div class="form-group">
<label for="password">Password</label>
<input class="form-control" name="password" placeholder="Password">
</div>
<div class="form-group">
<label for="confirm_password">Confirm Password</label>
<input class="form-control" name="confirm_password" placeholder="Confirm">
</div>
<div class="form-group">
<label for="permissions">User Permissions</label>

<select name="permissions" class="form-control">
                <option value="1" <?php if($permissions==1) echo 'selected'; ?>>1 - Viewer (Can View All Devices Assigned To User)</option>
                <option value="2" <?php if($permissions==2) echo 'selected'; ?>>2 - Restricted Editor (Can Edit All Devices Assigned To User)</option>
                <option value="3" <?php if($permissions==3) echo 'selected'; ?>>3 - Full Editor (Can Edit All Devices On the Server)</option>
                <option value="4" <?php if($permissions==4) echo 'selected'; ?>>4 - Manager (Can Add and Remove Devices)</option>
                <option value="5" <?php if($permissions==5) echo 'selected'; ?>>5 - Full Administrator (Can Add and Remove Users)</option>
</select>
</div>
<div class="box">
<h4>Allowed Devices</h4>
<?php foreach ($pluginOptions as $p): ?>
<label>
<input type="checkbox" name="allowedPlugins[]" value="<?= $p['id'] ?>"
<?= in_array($p['id'],$currentAllowedPlugins)?'checked':'' ?>>
<?= htmlspecialchars($p['name']) ?>
</label><br>
<?php endforeach; ?>
</div>

<div class="box">
<h4>Custom Navagation Bar</h4>

<label>
<input type="checkbox" name="useCoustomNav1" value="1" <?= $useCoustomNav1 ? 'checked':'' ?>>
Enable Custom Nav
</label>

<hr>

<h4>Hot Buttons</h4>

<select  style="color: black;"id="deviceSelect">
    <option value="">Select Device</option>
    <?php foreach ($pluginOptions as $p): ?>
        <option value="<?= $p['id'] ?>"
                data-plugin="<?= $p['pluginID'] ?>">
            <?= htmlspecialchars($p['name']) ?>
        </option>
    <?php endforeach; ?>
</select>

<select style="color: black;" id="optionSelect"></select>

<input  style="color: black;" id="customLabel" placeholder="Custom Name">

<button style="color: black;" type="button" onclick="addNav()">Add Button</button>

<hr>

<div id="navList"></div>

<input type="hidden" name="nav_json" id="nav_json">

</div>

<button class="btn btn-primary">Save</button>

</form>

</div>

<script>
const devices = <?= json_encode($pluginOptions) ?>;
const navData = <?= json_encode($currentNavElements) ?>;
const options = <?= json_encode($customNavOptions ?? []) ?>;

let list = [...navData];

function render() {
    const box = document.getElementById('navList');
    box.innerHTML = "";

    list.forEach((item, i) => {

        box.innerHTML += `
        <div class="nav-item">
            <b>${item.label || item.name}</b><br>
            <small>${item.path}</small><br>

            <button style="color: black;" type="button" onclick="removeItem(${i})">Delete</button>
        </div>`;
    });

    document.getElementById('nav_json').value = JSON.stringify(list);
}

function addNav() {

    const deviceId = document.getElementById('deviceSelect').value;
    const opt = document.getElementById('optionSelect');
    const label = document.getElementById('customLabel').value || opt.options[opt.selectedIndex].text;
    
   

    if (!deviceId || !opt.value) return;

    list.push({
        device_id: deviceId,
        name: opt.options[opt.selectedIndex].text,
        path: opt.value,
        label: label
    });

    render();
}

function removeItem(i) {
    list.splice(i,1);
    render();
}

document.getElementById('deviceSelect').addEventListener('change', function() {

    const id = this.value;
    const plugin = this.options[this.selectedIndex].dataset.plugin;

    const optBox = document.getElementById('optionSelect');
    optBox.innerHTML = "";

    if (!options[plugin]) return;

    options[plugin].forEach(o => {
    optBox.style.color = "black";
        optBox.innerHTML += `<option value="${o.path.replace('{id}', id)}">${o.name}</option>`;
    });
});

render();
</script>

</body>
</html>