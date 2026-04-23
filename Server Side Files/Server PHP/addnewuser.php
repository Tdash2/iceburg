<?php
include "config.php";
session_start();

if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}

if (!validateUserSession($conn, 5)) { // Adjust required permission level if needed
    showAccessDenied();
    exit;
}

// --- Registration Logic ---
$notification = "";

// Hash function
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function registerUser($username, $password, $permissions, $allowedPlugins, $conn) {
    $hashedPassword = hashPassword($password);
    $allowedPluginsJson = json_encode($allowedPlugins);

    $stmt = $conn->prepare("INSERT INTO `Admin Users` (UserEmail, UserPassword, UserPermissions, allowedPlugins) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        return "Prepare failed: " . $conn->error;
    }

    $stmt->bind_param("ssis", $username, $hashedPassword, $permissions, $allowedPluginsJson);

    if ($stmt->execute()) {
        return "User registered successfully.";
    } else {
        return "Error: " . htmlspecialchars($stmt->error);
    }
}

// Fetch all plugins
$pluginOptions = [];
$result = $conn->query("SELECT id, name FROM `devices` ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pluginOptions[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirm_password = trim($_POST["confirm_password"] ?? "");
    $permissions = intval($_POST["permissions"] ?? 1);
    $allowedPlugins = $_POST["allowedPlugins"] ?? []; // array of selected plugin IDs

    if ($password !== $confirm_password) {
        $notification = "Error: Passwords do not match.";
    } elseif (!empty($username) && !empty($password)) {
        $notification = registerUser($username, $password, $permissions, $allowedPlugins, $conn);
    } else {
        $notification = "Error: All fields are required.";
    }
}
?>
<?php include "header.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add User</title>
<style>
body { background-color: #232323; color: #FFF; }
</style>
</head>
<body class="bg-light">

<div class="container">
    <div class="py-5 text-center">
        <h2>Create an Account</h2>
        <p>Register a new user to Iceburg.</p>
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
            <input type="text" class="form-control" id="username" name="username" placeholder="Enter user email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
        </div>
        <div class="form-group">
            <label for="permissions">User Permissions</label>
            <select class="form-control" id="permissions" name="permissions" required>
                <option value="1">1 - Viewer (Can View All Devices Assigned To User) </option>
                <option value="2">2 - Restricted Editor (Can Edit All Devices Assigned To User)</option>
                <option value="3">3 - Full Editor (Can Edit All Devices On the Server)</option>
                <option value="4">4 - Manager (Can Add and Remove Devices)</option>
                <option value="5">5 - Full Administrator (Can Add and Remove Users)</option>
            </select>
        </div>

        <!-- Plugin Selection -->
        <div class="form-group">
            <label>Allowed Plugins</label><br>
            <?php foreach ($pluginOptions as $plugin): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="plugin_<?php echo $plugin['id']; ?>" name="allowedPlugins[]" value="<?php echo $plugin['id']; ?>">
                    <label class="form-check-label" for="plugin_<?php echo $plugin['id']; ?>"><?php echo htmlspecialchars($plugin['name']); ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
        <a class="btn btn-primary" href="viewusers.php">Back</a>
    </form>
</div>

</body>
</html>
