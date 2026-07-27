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
session_write_close();
$VIDEHub_HOST = "";

$id= $_GET['id'];

$stmt = $conn->prepare("SELECT ip,name FROM `devices` WHERE pluginID = 16 AND id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($VIDEHub_HOST,$deckname);
if (!$stmt->fetch()) {
    echo("No Device Found");
    exit;
}
$stmt->close();



$deckid= $_GET['id'];
$hyperdeckIP   = $VIDEHub_HOST;

?>
<?php
include "../header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<title>HyperDeck Clip Browser</title>

<style>

body{
    background:#1b1b1b;
    color:#eee;
    font-family:Arial,Helvetica,sans-serif;
    margin:20px;
}

h2{
    margin:0 0 15px 0;
}

#toolbar{
    display:flex;
    gap:10px;
    margin-bottom:15px;
    align-items:center;
}

input{
    flex:1;
    padding:8px;
    background:#333;
    color:white;
    border:1px solid #555;
    font-size:15px;
}

button{
    background:#0078d7;
    color:white;
    border:none;
    padding:8px 18px;
    cursor:pointer;
}

button:hover{
    background:#005fa3;
}

#status{
    margin-bottom:10px;
    font-weight:bold;
}

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#444;
    position:sticky;
    top:0;
}

th{
    text-align:left;
    padding:10px;
}

td{
    padding:8px;
    border-bottom:1px solid #333;
}

tbody tr{
    cursor:pointer;
}

tbody tr:hover{
    background:#333;
}

.selected{
    background:#0a5cff !important;
}

#tableContainer{
    height:68%;
    overflow-y:auto;
    border:1px solid #444;
}

#footer{
    margin-top:10px;
    color:#bbb;
}

#dropZone{
    border:2px dashed #666;
    border-radius:6px;
    padding:20px;
    text-align:center;
    margin-bottom:15px;
    background:#222;
    transition:0.2s;
}

#dropZone.drag{
    border-color:#0a84ff;
    background:#1e2d44;
}

#uploadStatus{
    margin-bottom:15px;
    color:#0a84ff;
}

#fileInput{
    display:none;
}
.deleteButton{
    background:#c00000;
    padding:5px 10px;
    font-size:12px;
}

.deleteButton:hover{
    background:#800000;
}
.delete-warning-overlay {
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.75);
    z-index:9999;
    justify-content:center;
    align-items:center;
}

.delete-warning-box {
    background:#b00000;
    color:white;
    width:500px;
    max-width:90%;
    padding:30px;
    border:5px solid red;
    border-radius:12px;
    text-align:center;
    font-size:22px;
    box-shadow:0 0 40px black;
}

.delete-warning-box h1 {
    font-size:42px;
    margin-top:0;
}

.delete-btn,
.cancel-btn {
    font-size:20px;
    padding:12px 25px;
    margin:15px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.delete-btn {
    background:#000;
    color:white;
}

.cancel-btn {
    background:white;
    color:#333;
}
</style>

</head>

<body>

<h2><?php echo $deckname;?> Recording</h2>

<div id="status">

</div>



<div id="uploadStatus"></div>

<div id="toolbar">

<input
type="text"
id="search"
placeholder="Search clips...">

<button onclick="loadClips()">
Refresh
</button>
 <button class=""
        onclick="record()">
        Record
        </button>
 <button class=""
        onclick="stop()">
        Stop
        </button>

<input type="file" id="fileInput" multiple>

</div>
<input type="file" id="fileInput" hidden>
<div id="tableContainer">

<table id="clipTable">

<thead>

<tr>
    <th width="60">ID</th>
    <th>Name</th>
    <th width="170">Codec</th>
    <th width="120">Format</th>
    <th width="120">Duration</th>
    <th width="90">REG #</th>
</tr>

</thead>

<tbody>

</tbody>

</table>

</div>

<div id="footer">

/\ \/: Move &nbsp;&nbsp;
Enter: Cue Clip &nbsp;&nbsp;
Double Click: Cue&nbsp;&nbsp;
S: Stop&nbsp;&nbsp;
P: Play&nbsp;&nbsp;
R: Record&nbsp;&nbsp;
CTRL+DEL: Deleate&nbsp;&nbsp;
</div>

<div id="deleteWarning" class="delete-warning-overlay">
    <div class="delete-warning-box">
        <h1>WARNING!</h1>

        <p id="deleteWarningText"></p>

        <button id="confirmDeleteBtn" class="delete-btn">
            Delete
        </button>

        <button id="cancelDeleteBtn" class="cancel-btn">
            Cancel
        </button>
    </div>
</div>

<script>

let clips=[];
let selected=0;

function loadClips(){

fetch("hyperdeck.php?id=<?php echo $deckid;?>")

.then(r=>r.json())

.then(data=>{

    if(!data.connected){

        return;
    }



    clips=data.clips;

    drawTable();

});

}

function drawTable(){

const tbody=document.querySelector("#clipTable tbody");

tbody.innerHTML="";

const filter=document.getElementById("search").value.toLowerCase();

clips.forEach((clip,index)=>{

    if(!clip.name.toLowerCase().includes(filter))
        return;

    const row=document.createElement("tr");

    row.dataset.id=clip.id;
    row.dataset.index=index;

    row.innerHTML=`

    <td>${clip.id}</td>
    <td>${clip.name}</td>
    <td>${clip.codec}</td>
    <td>${clip.format}</td>
    <td>${clip.duration}</td>

    <td>

       
    </td>

    `;
    row.onclick=function(){
        select(index);
    };

    row.ondblclick=function(){
        cue(index);
    };

    tbody.appendChild(row);

});

highlight();

}

function select(index){
    selected=index;
    highlight(false);
}
/////////////////////////////////////////////////////////
// Delete Clip
/////////////////////////////////////////////////////////

function deleteClip(filename, event){

    // Stop row selection/double click
    event.stopPropagation();


    const warning =
    "Deleting this clip will affect the deck's timecode.<br><br>" +
    "Clip:<br><b>" +
    filename +
    "</b><br><br>" +
    "Are you sure you want to delete this file?";


    const modal = document.getElementById("deleteWarning");
    const text = document.getElementById("deleteWarningText");

    text.innerHTML = warning;
    modal.style.display = "flex";


    document.getElementById("cancelDeleteBtn").onclick = function(){
        modal.style.display = "none";
    };


    document.getElementById("confirmDeleteBtn").onclick = function(){

        modal.style.display = "none";


        fetch("delete.php", {
            method:"POST",
            headers:{
                "Content-Type":"application/x-www-form-urlencoded"
            },
            body:
            "filename=" + encodeURIComponent(filename)+
            "&id=" + "<?php echo $deckid;?>"
        })

        .then(r=>r.text())

        .then(result=>{

            document.getElementById("status").innerHTML =
            result;

            loadClips();

        })

        .catch(()=>{

            document.getElementById("status").innerHTML =
            "Delete failed";

        });

    };

}
function highlight(scroll=false){

    document.querySelectorAll("#clipTable tbody tr").forEach(r=>{
        r.classList.remove("selected");
    });

    const row=document.querySelector(
        `tr[data-index='${selected}']`
    );

    if(row){

        row.classList.add("selected");

        if(scroll){
            row.scrollIntoView({
                block:"nearest"
            });
        }
    }
}

function cue(index){

fetch("hyperdeck.php?id=<?php echo $deckid;?>&cue="+clips[index].id);



}
function record(index){
fetch("hyperdeck.php?id=<?php echo $deckid;?>&record");
}
function stop(index){
fetch("hyperdeck.php?id=<?php echo $deckid;?>&stop");
}
function play(index){
fetch("hyperdeck.php?id=<?php echo $deckid;?>&play");
}

function timecode(){

fetch("hyperdeck.php?id=<?php echo $deckid;?>&timecode=1")

.then(r => r.json())

.then(data => {

    if(data.connected){

        document.getElementById("status").innerHTML =
            "Timecode: " + data.timecode;

    } else {

        document.getElementById("status").innerHTML =
            "Deck Offline";

    }

})
.catch(err => {

    document.getElementById("status").innerHTML =
        "Connection Error";

});

}



/////////////////////////////////////////////////////////
// Keyboard control
/////////////////////////////////////////////////////////

document.addEventListener("keydown",function(e){

if(e.key==="ArrowDown"){

if(selected<clips.length-1){
selected++;
highlight(true);
}

}

if(e.key==="ArrowUp"){

if(selected>0){
selected--;
highlight(true);
}

}

if(e.key==="Enter"){
cue(selected);
}

if(e.key==="Home"){
selected=0;
highlight(true);
}

if(e.key==="End"){
selected=clips.length-1;
highlight(true);
}

if(e.key==="s" || e.key==="S"){

stop();
}
if(e.key==="p" || e.key==="P"){

play();
}
if(e.key==="r" || e.key==="R"){

record();
}
if(e.key==="Delete" && e.ctrlKey ){

    if(clips[selected]){
        deleteClip(clips[selected].name, e);
    }

}
});

document.getElementById("search").addEventListener("keyup",drawTable);

loadClips();
setInterval(loadClips,1000);
setInterval(timecode,200);

</script>

</body>
</html>