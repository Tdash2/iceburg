<?php
include "../config.php";
session_start();




// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, $_REQUEST['id'])) {
    showAccessDenied();
    exit;
}
session_write_close();
$VIDEHub_HOST = "";

$id= $_REQUEST['id'];

$stmt = $conn->prepare("SELECT ip FROM `devices` WHERE pluginID = 16 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();




$deckIP   = $VIDEHub_HOST;



$deckPort = 9993;

function sendCommand($command)
{
    global $deckIP, $deckPort;

    $fp = @fsockopen($deckIP, $deckPort, $errno, $errstr, 3);

    if (!$fp) {
        return false;
    }

    stream_set_timeout($fp, 0, 200000); // 200 ms

    // Read connection banner
    while (($line = fgets($fp)) !== false) {
        if (trim($line) == "")
            break;
    }

    fwrite($fp,$command."\n");

    $response="";

    while(!feof($fp))
    {
        $line=fgets($fp);

        if($line===false)
            break;

        $response.=$line;

        if(trim($line)=="")
            break;
    }

    fclose($fp);

    return $response;
}
////////////////////////////////////////////////////
// Get Current Timecode
////////////////////////////////////////////////////

if(isset($_GET['timecode']))
{
    $response = sendCommand("transport info");

    header("Content-Type: application/json");

    if($response === false)
    {
        echo json_encode([
            "connected" => false
        ]);
        exit;
    }

    $timecode = "";

    if(preg_match('/timecode:\s*([0-9:;]+)/i', $response, $m))
    {
        $timecode = $m[1];
    }

    echo json_encode([
        "connected" => true,
        "timecode" => $timecode
    ]);

    exit;
}

////////////////////////////////////////////////////
// Cue Clip
////////////////////////////////////////////////////

if(isset($_GET['cue']))
{

    $id=(int)$_GET['cue'];

    sendCommand("goto: clip id:$id");

    echo "OK";

    exit;
}

////////////////////////////////////////////////////
// Record
////////////////////////////////////////////////////

if(isset($_GET['record']))
{



    sendCommand("record");

    echo "OK";

    exit;
}

////////////////////////////////////////////////////
// Stop
////////////////////////////////////////////////////

if(isset($_GET['stop']))
{



    sendCommand("stop");

    echo "OK";

    exit;
}
////////////////////////////////////////////////////
// play
////////////////////////////////////////////////////

if(isset($_GET['play']))
{



    sendCommand("play: single clip: true");

    echo "OK";

    exit;
}


////////////////////////////////////////////////////
// Get Clip List
////////////////////////////////////////////////////

$response=sendCommand("disk list");

if($response===false)
{
    header("Content-Type: application/json");

    echo json_encode([
        "connected"=>false
    ]);

    exit;
}

$clips=[];

$lines=explode("\n",$response);

foreach($lines as $line)
{

    $line=trim($line);

    if(!preg_match('/^(\d+):\s+(.*)$/',$line,$m))
        continue;

    $id=$m[1];
    $rest=$m[2];

    preg_match('/(.*?)\s+(QuickTime.*?|DNx.*?|H\.264.*?|H\.265.*?)\s+([0-9A-Za-z]+)\s+([0-9:;]+)$/',$rest,$parts);

    if(count($parts)==5)
    {
        $clips[]=[
            "id"=>$id,
            "name"=>$parts[1],
            "codec"=>$parts[2],
            "format"=>$parts[3],
            "duration"=>$parts[4]
        ];
    }
    else
    {
        $clips[]=[
            "id"=>$id,
            "name"=>$rest,
            "codec"=>"",
            "format"=>"",
            "duration"=>""
        ];
    }

}




header("Content-Type: application/json");

echo json_encode([
    "connected"=>true,
    "clips"=>$clips
],JSON_PRETTY_PRINT);

?>