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
        CURLOPT_TIMEOUT => 10,
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

    die("Failed to login to Magewell decoder.");
}

$message = '';

/*
|--------------------------------------------------------------------------
| HANDLE SOURCE SWITCHING
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['switch_source'])
) {

    $sourceType = $_POST['source_type'] ?? '';
    $sourceName = $_POST['source_name'] ?? '';

    if ($sourceName !== '') {

        $ndiFlag =
            ($sourceType === 'ndi')
            ? 'true'
            : 'false';

        $url = "http://{$magewellIp}/mwapi?method=set-channel"
            . "&ndi-name={$ndiFlag}"
            . "&name=" . urlencode($sourceName);

        $result = magewellRequest($url, $cookieFile);

        if (
            $result['success'] &&
            ($result['data']['status'] ?? -1) === 0
        ) {
            $message =
                "Switched to source: "
                . htmlspecialchars($sourceName);
        } else {
            $message = "Failed to switch source.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| HANDLE VIDEO MODE CHANGE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['set_video_mode'])
) {

    $width       = intval($_POST['width'] ?? 0);
    $height      = intval($_POST['height'] ?? 0);
    $fieldRate   = intval($_POST['field_rate'] ?? 0);
    $aspectRatio = floatval($_POST['aspect_ratio'] ?? 1.777777);
    $interlaced  =
        ($_POST['interlaced'] ?? 'false') === 'true'
        ? 'true'
        : 'false';

    $url = "http://{$magewellIp}/mwapi?method=set-video-mode"
        . "&width={$width}"
        . "&height={$height}"
        . "&field-rate={$fieldRate}"
        . "&aspect-ratio={$aspectRatio}"
        . "&interlaced={$interlaced}";

    $result = magewellRequest($url, $cookieFile);

    if (
        $result['success'] &&
        ($result['data']['status'] ?? -1) === 0
    ) {
        $message = "Output format updated.";
    } else {
        $message = "Failed to update output format.";
    }
}

/*
|--------------------------------------------------------------------------
| HANDLE HDMI OUTPUT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['set_hdmi_output'])
) {

    $enabled =
        ($_POST['enabled'] ?? 'true') === 'true'
        ? 'true'
        : 'false';

    $url = "http://{$magewellIp}/mwapi?method=set-hdmi-output"
        . "&enabled={$enabled}";

    $result = magewellRequest($url, $cookieFile);

    if (
        $result['success'] &&
        ($result['data']['status'] ?? -1) === 0
    ) {
        $message = "HDMI output updated.";
    } else {
        $message = "Failed to update HDMI output.";
    }
}

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
include "../header.php";
?>
<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <title>Decoder Control <?php echo $namee; ?></title>
   

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #101010;
            color: white;
            margin: 0;
            padding-left: 20px;
            padding-right: 20px;
        }

        h1 {
            margin-top: 0;
        }

        h2 {
            margin-top: 0;
        }

        .section {
            background: #1d1d1d;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message {
            background: #2d2d2d;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .current {
            color: #4caf50;
            font-weight: bold;
            font-size: 18px;
        }

        .source-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fill,
                minmax(280px, 1fr)
            );
            gap: 12px;
        }

        .source-card {
            background: #2a2a2a;
            padding: 14px;
            border-radius: 6px;
        }

        .source-name {
            font-weight: bold;
            margin-bottom: 10px;
            word-break: break-word;
        }

        .source-meta {
            font-size: 12px;
            color: #bbb;
            margin-bottom: 12px;
            word-break: break-word;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 4px;
            padding: 10px;
            cursor: pointer;
            background: #2196f3;
            color: white;
            font-size: 14px;
        }

        button:hover {
            background: #42a5f5;
        }

        select {
            width: 100%;
            padding: 10px;
            background: #2a2a2a;
            color: white;
            border: 1px solid #444;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .empty {
            color: #999;
            font-style: italic;
        }

    </style>

</head>
<body>
 <br>
<h1><?php echo $namee; ?></h1>
 <p><?php echo $VIDEHub_HOST; ?> </p>
 <br>
  <br>
<?php if ($message): ?>

    <div class="message">
        <?= $message ?>
    </div>

<?php endif; ?>

<div class="section">

    <h2>Current Source</h2>

    <?php if ($currentName): ?>

        <div class="current">
            <?= htmlspecialchars($currentName) ?>
            (<?= htmlspecialchars($currentType) ?>)
        </div>

    <?php else: ?>

        <div class="empty">
            No active source.
        </div>

    <?php endif; ?>

</div>

<div class="section">

    <h2>Output Format</h2>

    <form method="post">

        <input
            type="hidden"
            name="set_video_mode"
            value="1"
        >

        <select
            id="video_mode_select"
            onchange="updateVideoFields(this)"
        >

            <?php foreach ($videoModes as $mode): ?>

                <option
                    value='<?= json_encode($mode) ?>'
                    <?= !empty($mode['curr-mode'])
                        ? 'selected'
                        : '' ?>
                >

                    <?= $mode['width'] ?>x<?= $mode['height'] ?>

                    @

                    <?= number_format(
                        $mode['field-rate'] / 100,
                        2
                    ) ?>

                    <?= !empty($mode['interlaced'])
                        ? 'i'
                        : 'p' ?>

                </option>

            <?php endforeach; ?>

        </select>

        <input type="hidden" name="width" id="width">
        <input type="hidden" name="height" id="height">
        <input type="hidden" name="field_rate" id="field_rate">
        <input type="hidden" name="aspect_ratio" id="aspect_ratio">
        <input type="hidden" name="interlaced" id="interlaced">

        <button type="submit">
            Apply Output Format
        </button>

    </form>

</div>



<div class="section">

    <h2>NDI Sources</h2>

    <?php if (count($ndiSources) === 0): ?>

        <div class="empty">
            No NDI sources found.
        </div>

    <?php else: ?>

        <div class="source-grid">

            <?php foreach ($ndiSources as $source): ?>

                <div class="source-card">

                    <div class="source-name">
                        <?= htmlspecialchars(
                            $source['ndi-name']
                        ) ?>
                    </div>

                    <div class="source-meta">
                        <?= htmlspecialchars(
                            $source['ip-addr']
                        ) ?>
                    </div>

                    <form method="post">

                        <input
                            type="hidden"
                            name="switch_source"
                            value="1"
                        >

                        <input
                            type="hidden"
                            name="source_type"
                            value="ndi"
                        >

                        <input
                            type="hidden"
                            name="source_name"
                            value="<?= htmlspecialchars(
                                $source['ndi-name']
                            ) ?>"
                        >

                        <button type="submit">
                            Select NDI Source
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<div class="section">

    <h2>Saved Sources</h2>

    <?php if (count($savedSources) === 0): ?>

        <div class="empty">
            No saved sources found.
        </div>

    <?php else: ?>

        <div class="source-grid">

            <?php foreach ($savedSources as $source): ?>

                <div class="source-card">

                    <div class="source-name">
                        <?= htmlspecialchars(
                            $source['name']
                        ) ?>
                    </div>

                    <div class="source-meta">
                        <?= htmlspecialchars(
                            $source['url']
                        ) ?>
                    </div>

                    <form method="post">

                        <input
                            type="hidden"
                            name="switch_source"
                            value="1"
                        >

                        <input
                            type="hidden"
                            name="source_type"
                            value="saved"
                        >

                        <input
                            type="hidden"
                            name="source_name"
                            value="<?= htmlspecialchars(
                                $source['name']
                            ) ?>"
                        >

                        <button type="submit">
                            Select Saved Source
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<script>

function updateVideoFields(select)
{
    const mode = JSON.parse(select.value);

    document.getElementById('width').value =
        mode.width;

    document.getElementById('height').value =
        mode.height;

    document.getElementById('field_rate').value =
        mode['field-rate'];

    document.getElementById('aspect_ratio').value =
        mode['aspect-ratio'];

    document.getElementById('interlaced').value =
        mode.interlaced
        ? 'true'
        : 'false';
}

window.addEventListener('load', function () {

    const select =
        document.getElementById(
            'video_mode_select'
        );

    if (select) {
        updateVideoFields(select);
    }
});

</script>

</body>
</html>

