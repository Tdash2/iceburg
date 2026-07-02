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
$idd= $_GET['id'];
$stmt = $conn->prepare("SELECT ip, username, password,name FROM `devices` WHERE pluginID = 15 AND id=?");
$stmt->bind_param("i", $idd);
$stmt->execute();
$stmt->bind_result($TARGE,$username,$password,$namee);
if (!$stmt->fetch()) {
    //echo("No Device Found");
    exit;
}
$stmt->close();





$TARGET = $TARGE . ":7000";

if (isset($_GET['proxy'])) {

    $path = $_GET['proxy'];
    $url = $TARGET . $path;

    // Preserve query parameters except "proxy"
    $query = $_GET;
    unset($query['proxy']);

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

    // Forward request body (JSON, forms, etc.)
    $body = file_get_contents('php://input');
    if ($body !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    // Forward request headers
    $headers = [];

    foreach (getallheaders() as $name => $value) {

        $lower = strtolower($name);

        // Don't forward these
        if (in_array($lower, [
            'host',
            'content-length'
        ])) {
            continue;
        }

        $headers[] = "$name: $value";
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);

    if ($response === false) {
        http_response_code(500);
        die(curl_error($ch));
    }

    // Return same status code
    http_response_code(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    // Return same content type
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if ($contentType) {
        header("Content-Type: $contentType");
    }

    echo $response;

    curl_close($ch);
    exit;

}else{

?>





<!DOCTYPE html>

<html>
<meta charset="UTF-8">
<?php
include "../header.php";
?>
<head>
    <title>Intercom Matrix Cross Points</title>
    <link rel="stylesheet" href="style.css">
</head>



    <div id="busyOverlay" style="
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.4);
    z-index:9999;
    color:white;
    font-size:24px;
    font-weight:bold;
    align-items:center;
    justify-content:center;
">
        Applying Party Line Changes...
    </div>


    <div class="body2">

        <h1>Port Labels</h1>

        <div class="grid">

            <div class="panel" style="    background: transparent;">
                <h2>Outputs</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody id="outputs"></tbody>
                </table>
            </div>

            <div class="panel" style="    background: transparent;">
                <h2>Inputs</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody id="inputs"></tbody>
                </table>
            </div>
        </div>       
    </div>

</html>

<script>
async function loadLabels() {
    showBusy("Loading Labels");
    const response =
        await fetch('?id=<?php echo $idd;?>&proxy=/api/labels');

    const data =
        await response.json();

    renderOutputs(data.outputs);
    renderInputs(data.inputs);
    hideBusy();
}

function showBusy(message = "Applying Party Line Changes...") {
    const overlay = document.getElementById("busyOverlay");

    overlay.innerText = message;
    overlay.style.display = "flex";
}

function hideBusy() {
    document.getElementById("busyOverlay").style.display = "none";
}

function renderOutputs(outputs) {
    const body =
        document.getElementById('outputs');

    body.innerHTML = '';

    outputs.forEach(o => {
        const row =
            document.createElement('tr');

        row.innerHTML = `
            <td>${o.number}</td>
            <td>
                <input
                    id="out-${o.number}"
                    value="${o.name}">
            </td>
        `;

        const input =
            row.querySelector('input');

        input.addEventListener(
            'blur',
            () => saveOutput(o.number)
        );

        body.appendChild(row);
    });
}

function renderInputs(inputs) {
    const body =
        document.getElementById('inputs');

    body.innerHTML = '';

    inputs.forEach(i => {
        const row =
            document.createElement('tr');

        row.innerHTML = `
            <td>${i.number}</td>
            <td>
                <input
                    id="in-${i.number}"
                    value="${i.name}">
            </td>
        `;

        const input =
            row.querySelector('input');

        input.addEventListener(
            'blur',
            () => saveInput(i.number)
        );

        body.appendChild(row);
    });
}

async function saveOutput(number) {
    showBusy("Saving Labels");
    const el =
        document.getElementById(`out-${number}`);

    await fetch('?id=<?php echo $idd;?>&proxy=/api/outputlabel',
        {
            method: 'POST',
            headers:
            {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                number,
                name: el.value
            })
        });
    hideBusy();
}

async function saveInput(number) {
    showBusy("Saving Labels");
    const el =
        document.getElementById(`in-${number}`);

    await fetch('?id=<?php echo $idd;?>&proxy=/api/inputlabel',
        {
            method: 'POST',
            headers:
            {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                number,
                name: el.value
            })
        });
    hideBusy();
}

loadLabels();
</script>
<?php
}
?>
