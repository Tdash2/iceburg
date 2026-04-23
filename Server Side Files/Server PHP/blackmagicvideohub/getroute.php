<?php
include "../config.php";
session_start();



if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}

if (!validateUserSession($conn, 1, $_GET['id'])) {
    showAccessDenied();
    exit;
}

$id = (int)$_GET['id'];
$outputNumber = isset($_GET['output']) ? (int)$_GET['output'] : null;

if ($outputNumber === null) {
    echo "No output specified.";
    exit;
}

// Get Videohub IP
$stmt = $conn->prepare("SELECT ip FROM devices WHERE pluginID = 2 AND id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);

if (!$stmt->fetch()) {
    echo "No Device Found";
    exit;
}
$stmt->close();

$socket = fsockopen($VIDEHub_HOST, 9990, $errno, $errstr, 3);
if (!$socket) {
    Echo "no connection";
    exit;
}

$inRoutingBlock = false;
$currentSource = null;

while (!feof($socket)) {
    $line = trim(fgets($socket));

    // Detect start of routing table
    if ($line === "VIDEO OUTPUT ROUTING:") {
        $inRoutingBlock = true;
        continue;
    }

    // Stop when routing block ends
    if ($inRoutingBlock && $line === "") {
        break;
    }

    if ($inRoutingBlock) {
        // Format: OUTPUT INPUT
        [$out, $in] = array_pad(explode(" ", $line, 2), 2, null);

        if ((int)$out === $outputNumber) {
            $currentSource = (int)$in;
            break;
        }
    }
}

fclose($socket);

if ($currentSource === null) {
    echo "Output $outputNumber not found.";
    exit;
}

echo "Output $outputNumber is currently routed to Input $currentSource";
