<?php
include "config.php";


session_start();

// Redirect if already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $redirect = $_GET['redirect'] ?? 'home.php';
    header("Location: " . $redirect);
    exit;
}





// --- Helper: Get Client IP (Cloudflare-aware) ---
function getClientIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return 'unknown';
}

// --- Log to auditlogs ---
function logAudit($conn, $userId, $changeType, $details) {
    $detailsJson = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("INSERT INTO auditlogs (userid, changetype, changedetails) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $changeType, $detailsJson);
    $stmt->execute();
    $stmt->close();
}

// --- Verify User Credentials ---
function verifyLogin($username, $password, $conn) {
    $stmt = $conn->prepare("SELECT id, UserPassword, UserPermissions FROM `Admin Users` WHERE UserEmail = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($userId, $hashedPassword, $userPermissions);
    $stmt->fetch();
    $stmt->close();

    if ($hashedPassword !== null) {
        if (password_verify($password, $hashedPassword)) {
            return ['id' => $userId, 'permissions' => $userPermissions, 'success' => true];
        } else {
            return ['id' => $userId, 'permissions' => $userPermissions, 'success' => false];
        }
    }

    return ['id' => null, 'success' => false];
}

$ip = $_SERVER['REMOTE_ADDR'];

if (filter_var(
    $ip,
    FILTER_VALIDATE_IP,
    FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6
)) {
    if (in_array($ip, $localloginip)) {
    $username = "frontPanel";
    $password = "frontPanel";
    $redirect = $_POST["redirect"] ?? 'home.php';

    $ip = getClientIp();
    $timestamp = date("Y-m-d H:i:s");

    $user = verifyLogin($username, $password, $conn);

    if ($user['success']) {
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_permissions'] = $user['permissions'];

        // Log successful login
        $details = [
            'ip' => $ip,
            'timestamp' => $timestamp,
            'outcome' => 'success',
            'username' => $username
        ];
        logAudit($conn, $user['id'], 'login', $details);
        
        
     

        header("Location: " . $redirect);
        exit;
    } else {
        // Log failed login attempt (with user ID if username exists)
        $details = [
            'ip' => $ip,
            'timestamp' => $timestamp,
            'outcome' => 'failed',
            'username' => $username
        ];
        logAudit($conn, $user['id'], 'login', $details);

        echo "Invalid username or password";
    }
}
}



// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !(strcasecmp($_POST["username"], "frontPanel") == 0)) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $redirect = $_POST["redirect"] ?? 'home.php';

    $ip = getClientIp();
    $timestamp = date("Y-m-d H:i:s");

    $user = verifyLogin($username, $password, $conn);

    if ($user['success']) {
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_permissions'] = $user['permissions'];

        // Log successful login
        $details = [
            'ip' => $ip,
            'timestamp' => $timestamp,
            'outcome' => 'success',
            'username' => $username
        ];
        logAudit($conn, $user['id'], 'login', $details);
        
        
     

        header("Location: " . $redirect);
        exit;
    } else {
        // Log failed login attempt (with user ID if username exists)
        $details = [
            'ip' => $ip,
            'timestamp' => $timestamp,
            'outcome' => 'failed',
            'username' => $username
        ];
        logAudit($conn, $user['id'], 'login', $details);

        echo "Invalid username or password";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && !($_GET["username"] == null && !(strcasecmp($_GET["username"], "frontPanel") == 0))) {
    $username = $_GET["username"];
    $password = $_GET["password"];
    echo $username;
    $redirect = $_POST["redirect"] ?? 'home.php';

    $ip = getClientIp();
    $timestamp = date("Y-m-d H:i:s");

    $user = verifyLogin($username, $password, $conn);

    if ($user['success']) {
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_permissions'] = $user['permissions'];

        // Log successful login
        $details = [
            'ip' => $ip,
            'timestamp' => $timestamp,
            'outcome' => 'success',
            'username' => $username
        ];
        logAudit($conn, $user['id'], 'login', $details);
          
    

        header("Location: " . $redirect);
        exit;
    } else {
        // Log failed login attempt (with user ID if username exists)
        $details = [
            'ip' => $ip,
            'timestamp' => $timestamp,
            'outcome' => 'failed',
            'username' => $username
        ];
        logAudit($conn, $user['id'], 'login', $details);

        echo "Invalid username or password";
    }
}

// Preserve redirect for form
$redirect = $_GET['redirect'] ?? 'home.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="favicon.ico">
    <title>Iceberg</title>
    <style>
        /* General Reset */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #232323;
            color: #eee;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Container */
        .container {
            display: flex;
            justify-content: center;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Login Box */
        #div_login {
            background-color: #2c2c2c;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 30px 20px 20px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 400px; /* fixed max width */
            box-sizing: border-box;
        }

        #div_login h1 {
            margin: 0;
            padding: 10px 0;
            text-align: center;
            color: #fff;
            font-weight: bold;
            font-size: 28px;
        }

        #div_login img.logo {
            display: block;
            margin: 20px auto;
            width: 120px;
            height: auto;
                margin-bottom: 0px;
        }

        #div_login div {
            margin-top: 15px;
            text-align: center;
        }

        #div_login .textbox {
            width: 90%; /* reduced from 100% */
            max-width: 300px;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #555;
            background-color: #1e1e1e;
            color: #fff;
            font-size: 16px;
        }

        #div_login .textbox::placeholder {
            color: #aaa;
        }

        #div_login input[type=submit] {
            width: 90%; /* reduced from 100% */
            max-width: 300px;
            padding: 12px;
            margin-top: 20px;
            border: none;
            border-radius: 5px;
            background-color: #3a9ad9;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        #div_login input[type=submit]:hover {
            background-color: #2e8bc0;
        }

        /* Media Queries */
        @media screen and (max-width: 720px) {
            #div_login {
                padding: 20px;
                    margin-bottom: 0px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <form method="post" action="">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            <div id="div_login">
            <img src="Iceburg Log.png" alt="Logo" class="logo">
                <h1>Iceburg</h1>
                
                <div>
                    <input type="text" class="textbox" id="username" name="username" placeholder="Username" required>
                </div>
                <div>
                    <input type="password" class="textbox" id="password" name="password" placeholder="Password" required>
                </div>
                <div>
                    <input type="submit" value="Login" name="but_submit" id="but_submit" />
                </div>
            </div>
        </form>
    </div>
</body>
</html>
