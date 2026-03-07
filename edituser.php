<?php
include "config.php";
session_start();
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 5)) { // Only admins can edit users
    showAccessDenied();
    exit;
}

$notification = "";
$user_id = intval($_GET['id'] ?? 0);

if ($user_id <= 0) {
    die("Invalid user ID.");
}

// Fetch user data including allowedPlugins
$stmt = $conn->prepare("SELECT UserEmail, UserPermissions, allowedPlugins FROM `Admin Users` WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $permissions, $allowedPluginsJson);
if (!$stmt->fetch()) {
    die("User not found.");
}
$stmt->close();

// Decode allowed plugins
$currentAllowedPlugins = json_decode($allowedPluginsJson, true);
if (!is_array($currentAllowedPlugins)) {
    $currentAllowedPlugins = [];
}

// Fetch all plugins for selection list
$pluginOptions = [];
$result = $conn->query("SELECT id, pluginName FROM `deviceplugin` ORDER BY pluginName ASC");
while ($row = $result->fetch_assoc()) {
    $pluginOptions[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST["username"] ?? "");
    $new_password = trim($_POST["password"] ?? "");
    $confirm_password = trim($_POST["confirm_password"] ?? "");
    $new_permissions = intval($_POST["permissions"] ?? 1);

    // Selected plugin IDs
    $newAllowedPlugins = $_POST["allowedPlugins"] ?? [];
    $newAllowedPluginsJson = json_encode($newAllowedPlugins);

    if (!empty($new_password) && $new_password !== $confirm_password) {
        $notification = "Error: Passwords do not match.";
    } else {
        if (!empty($new_password)) {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE `Admin Users` 
                SET UserEmail=?, UserPassword=?, UserPermissions=?, allowedPlugins=? 
                WHERE id=?");
            $stmt->bind_param("ssisi", $new_username, $hashedPassword, $new_permissions, $newAllowedPluginsJson, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE `Admin Users` 
                SET UserEmail=?, UserPermissions=?, allowedPlugins=? 
                WHERE id=?");
            $stmt->bind_param("sisi", $new_username, $new_permissions, $newAllowedPluginsJson, $user_id);
        }

        if ($stmt->execute()) {
            $notification = "User updated successfully.";
            $username = $new_username;
            $permissions = $new_permissions;
            $currentAllowedPlugins = $newAllowedPlugins;
        } else {
            $notification = "Error updating user: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}
?>
<?php include "header.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User</title>
<style>
body {
    background-color: #232323;
    color: #FFF;
}
</style>
</head>
<body class="bg-light">

<div class="container">
    <div class="py-5 text-center">
        <h2>Edit User</h2>
        <p>Modify user details below. Leave password blank to keep the current password.</p>
    </div>

    <?php if(!empty($notification)): ?>
    <div class="alert alert-dismissible <?php echo (strpos($notification, 'Error') !== false ? 'alert-danger' : 'alert-success'); ?>">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo $notification; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="username">Email / Username</label>
            <input type="text" class="form-control" id="username" name="username"
                   value="<?php echo htmlspecialchars($username); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password (leave blank to keep current)</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                   placeholder="Re-enter password">
        </div>

        <div class="form-group">
            <label for="permissions">User Permissions</label>
            <select class="form-control" id="permissions" name="permissions" required>
                <option value="1" <?php if($permissions==1) echo 'selected'; ?>>1 - Viewer</option>
                <option value="2" <?php if($permissions==2) echo 'selected'; ?>>2 - Restricted Editor</option>
                <option value="3" <?php if($permissions==3) echo 'selected'; ?>>3 - Full Editor</option>
                <option value="4" <?php if($permissions==4) echo 'selected'; ?>>4 - Manager</option>
                <option value="5" <?php if($permissions==5) echo 'selected'; ?>>5 - Full Administrator</option>
            </select>
        </div>

        <!-- Plugin Selection -->
        <div class="form-group">
            <label>Allowed Plugins</label><br>
            <?php foreach ($pluginOptions as $plugin): ?>
                <?php $pid = $plugin['id']; ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input"
                           type="checkbox"
                           id="plugin_<?php echo $pid; ?>"
                           name="allowedPlugins[]"
                           value="<?php echo $pid; ?>"
                           <?php if (in_array($pid, $currentAllowedPlugins)) echo 'checked'; ?>>
                           
                    <label class="form-check-label" for="plugin_<?php echo $pid; ?>">
                        <?php echo htmlspecialchars($plugin['pluginName']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary">Update User</button>
        <a class="btn btn-primary" style="color: #FFF;" href="viewusers.php">Back</a>
    </form>
</div>

</body>
</html>
