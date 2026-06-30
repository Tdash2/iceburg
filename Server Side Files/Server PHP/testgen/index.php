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
$stmt = $conn->prepare("SELECT ip, username, password,name FROM `devices` WHERE pluginID = 13 AND id=?");
$stmt->bind_param("i", $idd);
$stmt->execute();
$stmt->bind_result($TARGET,$username,$password,$namee);
if (!$stmt->fetch()) {
    //echo("No Device Found");
    exit;
}
$stmt->close();



$TARGET = "10.244.0.76:8100";

// ----------------------
// PROXY EVERYTHING EXCEPT "/"
// ----------------------
if (isset($_GET['proxy'])) {

    $path = $_GET['proxy'];

    $url = $TARGET . $path;

    // preserve extra query params except proxy
    $query = $_GET;
    unset($query['proxy']);

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
    }

    $response = curl_exec($ch);

    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($contentType) {
        header("Content-Type: $contentType");
    }

    http_response_code(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    echo $response;

    curl_close($ch);
    exit;
}else{

?>





<!DOCTYPE html>
<html>
<?php
include "../header.php";
?>
<head>

<title>Test Signal Generator</title>



<style>
body{
    background:#232323;
    color:white;
}
</style>

</head>

<body style="font-family:sans-serif;padding:40px;">

<div class="container"style="
    padding-top: 80px" >

<div class="py-5 text-center">

</div>

<div class="form-group">
<label>First Line Text</label>
<input class="form-control" id="text1">
</div>

<div class="form-group">
<label>Second Line Text</label>
<input class="form-control" id="text2">
</div>

<div class="form-group">
<label>Output Format</label>

<select class="form-control" id="mode" onchange="setMode()">
<option value="1080p">1920x1080 @59.94p</option>
<option value="1080i">1920x1080 @59.94i</option>
<option value="720p">1280x720 @59.94p</option>
</select>

</div>

<div class="form-check" style>

<input
class="form-check-input"
type="checkbox"
id="showweb"
onchange="setWebUI()">

<label class="form-check-label">
Show WEB UI address on output
</label>

</div>

<br>

<div class="form-group">

<label>Preview</label>

<img
id="prev"
class="form-control"
src="?proxy=/preview.jpg"
width="640" style="
    height: 100%">

</div>

</div>

<script>

function setWebUI(){

    const fd=new FormData();

    fd.append("show",showweb.checked);

    fetch("?proxy=/set_webui&id=<?php echo $idd;?>",{
        method:"POST",
        body:fd
    });

}

function setMode(){

    const fd=new FormData();

    fd.append("mode",mode.value);

    fetch("?proxy=/mode&id=<?php echo $idd;?>",{
        method:"POST",
        body:fd
    });

}

async function refresh(){

    const r=await fetch("?proxy=/state&id=<?php echo $idd;?>");
    const s=await r.json();

    if(document.activeElement!==text1)
        text1.value=s.text1;

    if(document.activeElement!==text2)
        text2.value=s.text2;

    showweb.checked=s.show_web_ui;
    mode.value=s.output_mode;

}

function update(){

    const fd=new FormData();

    fd.append("text1",text1.value);
    fd.append("text2",text2.value);

    fetch("?proxy=/update&id=<?php echo $idd;?>",{
        method:"POST",
        body:fd
    });

}

function updateSpeed(){

    const fd=new FormData();

    fd.append("speed",speed.value);

    fetch("?proxy=/speed&id=<?php echo $idd;?>",{
        method:"POST",
        body:fd
    });

}

function toggleMute(){

    fetch("?proxy=/mute&id=<?php echo $idd;?>",{
        method:"POST"
    });

}

text1.oninput=update;
text2.oninput=update;

setInterval(function(){

    prev.src="?proxy=/preview.jpg&id=<?= $idd ?>&t=" + Date.now();

},300);

refresh();

setInterval(refresh,1000);

</script>

</body>

</html>

<?php
}
?>
