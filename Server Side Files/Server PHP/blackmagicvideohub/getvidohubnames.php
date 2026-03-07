<?php
include "../config.php";
session_start();


// Check permissions
if (!validateUserSession($conn, 0)) {
    http_response_code(403);
    echo json_encode(['error' => "User logged out."]);
    exit;
}
if (!validateUserSession($conn, 1, 2)) {
    http_response_code(403);
    echo json_encode(['error' => "Access denied."]);
    exit;
}

// Get device IP
$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => "No device ID provided."]);
    exit;
}

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 2 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);
if (!$stmt->fetch() || !$VIDEHub_HOST) {
    echo json_encode(['error' => "Device not found or IP missing."]);
    exit;
}
$stmt->close();

// Videohub connection
$VIDEHub_PORT = 9990;
$SOCKET_TIMEOUT = 2.0;

header('Content-Type: application/json; charset=utf-8');

$res = vh_get_full_status($VIDEHub_HOST, $VIDEHub_PORT, $SOCKET_TIMEOUT);
if(isset($res['error'])) {
    echo json_encode(['error' => $res['error']]);
    exit;
}

// Only return input/output labels
$labels = [
    'inputs' => $res['input_labels'] ?? [],
    'outputs' => $res['output_labels'] ?? []
];

echo json_encode($labels);
exit;


// ----------------------
// Helper functions below
// ----------------------

function vh_get_full_status($host, $port=9990, $timeout=2.0) {
    $ctx = stream_context_create();
    $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if(!$fp) return ['error' => "Could not connect to {$host}: {$errstr} ({$errno})"];
    
    stream_set_timeout($fp, (int)$timeout);
    stream_set_blocking($fp, true);

    $raw = '';
    $start = microtime(true);
    while (true) {
        $buf = @fgets($fp);
        if ($buf === false) break;
        $raw .= $buf;
        $meta = stream_get_meta_data($fp);
        if ((microtime(true) - $start) > ($timeout + 0.5)) break;
    }
    fclose($fp);

    $blocks = parse_vh_blocks($raw);
    $res = [];

    if (!empty($blocks['INPUT LABELS'])) {
        $labels = [];
        foreach ($blocks['INPUT LABELS'] as $line) {
            if (preg_match('/^\s*(\d+)\s+(.*)$/', $line, $m)) $labels[intval($m[1])] = $m[2];
        }
        $res['input_labels'] = $labels;
    }

    if (!empty($blocks['OUTPUT LABELS'])) {
        $labels = [];
        foreach ($blocks['OUTPUT LABELS'] as $line) {
            if (preg_match('/^\s*(\d+)\s+(.*)$/', $line, $m)) $labels[intval($m[1])] = $m[2];
        }
        $res['output_labels'] = $labels;
    }

    return $res;
}

function parse_vh_blocks($raw) {
    $lines = preg_split("/\r\n|\n|\r/", $raw);
    $blocks = [];
    $current = null;
    foreach ($lines as $ln) {
        $trim = rtrim($ln, "\r\n");
        if ($trim === '') { $current = null; continue; }
        if (preg_match('/^([A-Z0-9 _]+):\s*$/', $trim, $m)) { $current = trim($m[1]); $blocks[$current] = []; continue; }
        if ($current !== null) $blocks[$current][] = $trim;
    }
    return $blocks;
}
?>
