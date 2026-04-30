
<?php
//Database Settings
$servername = "localhost";
$username = "iceburg";
$password = "jfu5itjfitiejit5kfsfdgfdge8t43w";
$dbname = "iceburg";

//Auto Login IP. Any ip in this array will automaticaly be loged in with the username frontPanel and password frontPanel. This is usefull for a kiok. 
$localloginip = ["127.0.0.1", "10.176.71.113"];







$serverurl= $_SERVER['HTTP_HOST'] ?? '';


$host = $_SERVER['HTTP_HOST'] ?? '';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userPerm = 0;
function validateUserSession($conn, $requiredPermission = null, $pluginID = null) {

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Fetch latest user info including allowedPlugins
    $stmtt = $conn->prepare("SELECT id, UserPermissions, UserEmail, allowedPlugins FROM `Admin Users` WHERE id = ?");
    $stmtt->bind_param("i", $_SESSION['user_id']);
    $stmtt->execute();
    $stmtt->bind_result($userId, $userPermissions, $userEmail, $allowedPluginsJson);
    $stmtt->fetch();
    $stmtt->close();

    // If user was deleted
    if (!$userId) {
        session_destroy();
        return false;
    }

    // Decode allowed plugins
    $allowedPlugins = json_decode($allowedPluginsJson, true);
    if (!is_array($allowedPlugins)) {
        $allowedPlugins = [];
    }

    // Keep session values updated
    $_SESSION['user_permissions'] = $userPermissions;
    $_SESSION['UserEmail'] = $userEmail;
    $_SESSION['allowedPlugins'] = $allowedPlugins;
    $_SESSION['Userid'] = $userId;
$userPerm = $userPermissions;
    // Check required permission level
    if ($requiredPermission !== null && $userPermissions < $requiredPermission) {
        return false;
    }

    // Check plugin access only if a plugin ID was provided
    if ($pluginID !== null) {

        // Admins automatically bypass plugin restrictions
        if ($userPermissions >= 3) {
            return true;
        }

        // If pluginid is NOT in their allowedPlugins, deny access
        if (!in_array($pluginID, $allowedPlugins)) {
            return false;
        }
    }

    return true;
}



function showloggedout() {
    // Get the current full URL
    $currentUrl = $_SERVER['REQUEST_URI'];
    $redirectUrl = "/index.php?redirect=" . urlencode($currentUrl);
    ?>
    <!DOCTYPE html>
    <html lang="en">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <head>
        <meta charset="UTF-8">
        <title>Session Expired</title>
        <link href="style.css" rel="stylesheet" type="text/css">
        <style>
        body {
    background-color: #232323;
    color: #FFF;
}
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error-box { border: 1px solid #e74c3c; padding: 30px; display: inline-block; background: #fcebea; color: #c0392b; border-radius: 8px; }
            button, a.login-btn {
               
                padding: 10px 20px;
                font-size: 16px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
            button { background-color: #3498db; color: white; }
            button:hover { background-color: #2980b9; }
            a.login-btn { background-color: #00ba0e; color: white; }
            a.login-btn:hover { background-color: #00990c; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>Session Expired</h1>
            <p>Sorry, your session has expired. Please Login again.</p>
            <a href="/<?php echo htmlspecialchars($redirectUrl); ?>" class="login-btn">Login</a>
        </div>
    </body>
    </html>
    <?php
    header("Location: ".htmlspecialchars($redirectUrl));

    exit;
}


function showAccessDenied() {
    // Get the current full URL
    $currentUrl = $_SERVER['REQUEST_URI'];
    $redirectUrl = "index.php?redirect=" . urlencode($currentUrl);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta charset="UTF-8">
        <title>Access Denied</title>
        <link href="style.css" rel="stylesheet" type="text/css">
        <style>
        body {
    background-color: #232323;
    color: #FFF;
}
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error-box { border: 1px solid #e74c3c; padding: 30px; display: inline-block; background: #fcebea; color: #c0392b; border-radius: 8px; }
            button, a.login-btn {
                
                padding: 10px 20px;
                font-size: 16px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
            button { background-color: #3498db; color: white; }
            button:hover { background-color: #2980b9; }
            a.login-btn { background-color: #00ba0e; color: white; }
            a.login-btn:hover { background-color: #00990c; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>Access Denied</h1>
            <p>Sorry, you do not have permission to perform this action.</p>
            <button onclick="window.history.back();">Go Back</button>
            <a href="/home.php" class="login-btn">Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}?>