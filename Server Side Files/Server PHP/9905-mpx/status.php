<?php
include "../config.php";
session_start();


// Check permissions
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn,1, $_GET['id'])) {
    showAccessDenied();
    exit;
}


?>
<?php
// CONFIG

$VIDEHub_HOST = "";

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 3 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($x32);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();


if ($x32 == null){
echo "No Ip Provided!";
exit;
}


$url = "http://".$x32.":9002/getOid?oid=65281";

	$context = stream_context_create(array(
	    'http' => array(
	        'timeout' => 1   // Timeout in seconds
	    )
	));

// Fetch the JSON data from the URL
$response = file_get_contents($url,0, $context);

if ($response === false) {
header("HTTP/1.1 504 Gateway Timeout");
    exit;
}

// Decode JSON into associative array
$data = json_decode($response, true);

if (isset($data['data_updates']['oids'][0]['data_value'])) {
    $data_value = $data['data_updates']['oids'][0]['data_value'];
    echo "Friendly Name: " . $data_value;
} else {
header("HTTP/1.1 504 Gateway Timeout");
    exit;
}





?>
