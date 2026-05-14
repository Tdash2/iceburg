<?php
include "../config.php";
session_start();




// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, $_GET['id'])) {
    showAccessDenied();
    exit;
}
$VIDEHub_HOST = "";
$username   = '';
$password   = '';

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip, username, password,name FROM `devices` WHERE pluginID = 12 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST,$username,$password,$namee);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();




/*
|--------------------------------------------------------------------------
| Magewell Decoder Full Control Panel
|--------------------------------------------------------------------------
|
| Features:
| - List NDI Sources
| - List Saved Sources
| - Switch Inputs
| - Output Resolution Control
| - HDMI Enable/Disable
| - Current Active Source
| - Server-side PHP bridge
|
*/

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

$magewellIp = $VIDEHub_HOST;


$passwordHash = md5($password);

$cookieFile = sys_get_temp_dir() . '/magewell_cookie.txt';

/*
|--------------------------------------------------------------------------
| API REQUEST HELPER
|--------------------------------------------------------------------------
*/

function magewellRequest($url, $cookieFile)
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 1,
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return [
            'success' => false,
            'error' => curl_error($ch)
        ];
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if (!$json) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response',
            'raw' => $response
        ];
    }

    return [
        'success' => true,
        'data' => $json
    ];
}

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

function magewellLogin(
    $ip,
    $username,
    $passwordHash,
    $cookieFile
) {
    $url = "http://{$ip}/mwapi?method=login"
        . "&id=" . urlencode($username)
        . "&pass=" . urlencode($passwordHash);

    $result = magewellRequest($url, $cookieFile);

    if (
        !$result['success'] ||
        ($result['data']['status'] ?? -1) !== 0
    ) {
        return false;
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| LOGIN FIRST
|--------------------------------------------------------------------------
*/

if (!magewellLogin(
    $magewellIp,
    $username,
    $passwordHash,
    $cookieFile
)) {

     header("HTTP/1.1 504 Gateway Timeout");
    exit;
}

$message = '';


/*
|--------------------------------------------------------------------------
| GET CURRENT SOURCE
|--------------------------------------------------------------------------
*/

$currentName = '';
$currentType = '';

$currentResult = magewellRequest(
    "http://{$magewellIp}/mwapi?method=get-channel",
    $cookieFile
);

if ($currentResult['success']) {

    $currentName =
        $currentResult['data']['name'] ?? '';

    $currentType =
        ($currentResult['data']['ndi-name'] ?? false)
        ? 'NDI'
        : 'Saved';
}

/*
|--------------------------------------------------------------------------
| GET NDI SOURCES
|--------------------------------------------------------------------------
*/

$ndiSources = [];

$ndiResult = magewellRequest(
    "http://{$magewellIp}/mwapi?method=get-ndi-sources",
    $cookieFile
);

if ($ndiResult['success']) {
    $ndiSources =
        $ndiResult['data']['sources'] ?? [];
}

/*
|--------------------------------------------------------------------------
| GET SAVED SOURCES
|--------------------------------------------------------------------------
*/

$savedSources = [];

$savedResult = magewellRequest(
    "http://{$magewellIp}/mwapi?method=list-channels",
    $cookieFile
);

if ($savedResult['success']) {
    $savedSources =
        $savedResult['data']['channels'] ?? [];
}

/*
|--------------------------------------------------------------------------
| GET VIDEO MODES
|--------------------------------------------------------------------------
*/

$videoModes = [];

$videoModesResult = magewellRequest(
    "http://{$magewellIp}/mwapi?method=get-supported-video-modes",
    $cookieFile
);

if ($videoModesResult['success']) {
    $videoModes =
        $videoModesResult['data']['modes'] ?? [];
}

/*
|--------------------------------------------------------------------------
| GET HDMI OUTPUT STATUS
|--------------------------------------------------------------------------
*/

$hdmiOutputEnabled = true;

$hdmiResult = magewellRequest(
    "http://{$magewellIp}/mwapi?method=get-hdmi-output",
    $cookieFile
);

if ($hdmiResult['success']) {
    $hdmiOutputEnabled =
        $hdmiResult['data']['enabled'] ?? true;
}

?>



    <?php if ($currentName): ?>

    Video Sorce:
            <?= htmlspecialchars($currentName) ?>
            (<?= htmlspecialchars($currentType) ?>)
    

    <?php else: ?>

  
            No active source.
   

    <?php endif; ?>


<?php foreach ($videoModes as $mode): ?>

    <?php if (!empty($mode['curr-mode'])): ?>

        <p> Output Format: 
            <?= $mode['width'] ?>x<?= $mode['height'] ?>
            @
            <?= number_format($mode['field-rate'] / 100, 2) ?>
            <?= !empty($mode['interlaced']) ? 'i' : 'p' ?>
        </p>

    <?php endif; ?>

<?php endforeach; ?>










</body>
</html>

