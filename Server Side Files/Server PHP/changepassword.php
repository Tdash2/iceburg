<?php
include "config.php";
session_start();

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
if($isdemo){
    showDisabledForDemo();
    exit;
}


$notification = "";
$user_id = intval($_SESSION['user_id']); // Use session user ID

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Fetch current hashed password from DB using user ID
    $stmt = $conn->prepare("SELECT UserPassword FROM `Admin Users` WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current_password, $hashedPassword)) {
        $notification = "Error: Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $notification = "Error: New passwords do not match.";
    } elseif (empty($new_password)) {
        $notification = "Error: New password cannot be empty.";
    } else {
        // Update password
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE `Admin Users` SET UserPassword=? WHERE id=?");
        $stmt->bind_param("si", $new_hashed, $user_id);
        if ($stmt->execute()) {
            $notification = "Password updated successfully.";
        } else {
            $notification = "Error updating password: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-light">

<?php include "header.php"; ?>
<style>
body {
             background-color: #232323;
             color:#fff;
              }
</style>
<div class="container">
    <div class="py-5 text-center">
        <h2>Change Password</h2>
        <p>Update your account password below.</p>
    </div>

    <?php if(!empty($notification)): ?>
    <div class="alert alert-dismissible <?php echo (strpos($notification, 'Error') !== false ? 'alert-danger' : 'alert-success'); ?>">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?php echo $notification; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
        </div>
        <button type="submit" class="btn btn-primary">Change Password</button>
        <a class="btn btn-secondary" href="home.php">Back</a>
    </form>
</div>

</body>
</html>
