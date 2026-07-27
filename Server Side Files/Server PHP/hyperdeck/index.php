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
.deck-btn{
    font-size:32px;
    width:70px;
    height:70px;
    margin:10px;
    background:#0078d7;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.deck-btn:hover{
    background:#005fa3;
}
</style>

</head>

<body>

<h2><?php echo $deckname;?> Clip Browser</h2>

<div id="status">

</div>

<div id="dropZone">
Drag video files here or click To Upload Files
</div>

<div id="uploadStatus"></div>

<div id="toolbar">

<input
type="text"
id="search"
placeholder="Search clips...">

<button onclick="openPrintTable()">
    Print Clip List
</button>
<button onclick="exportTDC1000()">
    Export TDC-1000
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

<div id="renameDialog" class="delete-warning-overlay">
    <div class="delete-warning-box" style="background:#2c2c2c;border-color:#0a84ff;">
        <h1>Rename Clip</h1>

        <p>Enter a name for this clip before uploading.</p>

        <input
            type="text"
            id="renameInput"
            style="width:90%;padding:10px;font-size:18px;margin:15px 0;">

        <br>

        <button id="renameOkBtn" class="delete-btn">
            Upload
        </button>

        <button id="renameCancelBtn" class="cancel-btn">
            Cancel
        </button>
    </div>
</div>
<div id="deckDialog" class="delete-warning-overlay">
    <div class="delete-warning-box" style="background:#2c2c2c;border-color:#0a84ff;">
        <h1>Select Output</h1>

        <p>Select HyperDeck output:</p>

        <div style="margin:20px;">
            <button class="deck-btn" data-deck="A">A</button>
            <button class="deck-btn" data-deck="B">B</button>
            <button class="deck-btn" data-deck="C">C</button>
            <button class="deck-btn" data-deck="D">D</button>
        </div>

        <button id="deckCancelBtn" class="cancel-btn">
            Cancel
        </button>
    </div>
</div>
<script>
function promptForFilename(defaultName){

    return new Promise(resolve=>{

        const modal = document.getElementById("renameDialog");
        const input = document.getElementById("renameInput");

        input.value = defaultName;

        modal.style.display = "flex";

        input.focus();
        input.select();

        document.getElementById("renameOkBtn").onclick = function(){

            modal.style.display = "none";

            resolve(input.value.trim() || defaultName);

        };

        document.getElementById("renameCancelBtn").onclick = function(){

            modal.style.display = "none";

            resolve(null);

        };

        input.onkeydown = function(e){

            if(e.key==="Enter"){
                document.getElementById("renameOkBtn").click();
            }

            if(e.key==="Escape"){
                document.getElementById("renameCancelBtn").click();
            }

        };

    });

}

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

function selectDeck(){

    return new Promise(resolve=>{

        const modal = document.getElementById("deckDialog");

        modal.style.display="flex";


        document.querySelectorAll(".deck-btn").forEach(btn=>{

            btn.onclick=function(){

                modal.style.display="none";

                resolve(btn.dataset.deck);

            };

        });


        document.getElementById("deckCancelBtn").onclick=function(){

            modal.style.display="none";

            resolve(null);

        };


        document.addEventListener("keydown", function esc(e){

            if(e.key==="Escape"){

                modal.style.display="none";

                resolve(null);

                document.removeEventListener("keydown", esc);
            }

        });

    });

}
function getFPS(format){

    format = format.toUpperCase();

    // Common HyperDeck formats
    if(format.includes("23.98") || format.includes("2398"))
        return 23.98;

    if(format.includes("24"))
        return 24;

    if(format.includes("25"))
        return 25;

    if(format.includes("29.97") || format.includes("2997"))
        return 29.97;

    if(format.includes("30"))
        return 30;

    if(format.includes("50"))
        return 50;

    if(format.includes("59.94") || format.includes("5994"))
        return 59.94;

    if(format.includes("60"))
        return 60;


    // Default
    return 30;
}


function hyperDeckToTDC(duration, format){

    if(!duration)
        return "00:00:00:00";


    /*
        HyperDeck returns:
        HH:MM:SS;FF

        TDC-1000 wants:
        HH:MM:SS:FF
    */


    return duration
        .replace(";", ":");

}
function addTimecodes(tc1, tc2, fps){

    let a = tc1.replace(";"," :").split(":").map(Number);
    let b = tc2.replace(";"," :").split(":").map(Number);

    let frames = a[3] + b[3];
    let seconds = a[2] + b[2];
    let minutes = a[1] + b[1];
    let hours = a[0] + b[0];


    if(frames >= fps){
        frames -= fps;
        seconds++;
    }

    if(seconds >= 60){
        seconds -= 60;
        minutes++;
    }

    if(minutes >= 60){
        minutes -= 60;
        hours++;
    }


    return (
        String(hours).padStart(2,"0") + ":" +
        String(minutes).padStart(2,"0") + ":" +
        String(seconds).padStart(2,"0") + ":" +
        String(frames).padStart(2,"0")
    );

}

async function exportTDC1000(){

let deck = await selectDeck();

if(!deck)
    return;

deck = deck.toUpperCase();


    function getFPS(format){

        format = (format || "").toUpperCase();


        if(format.includes("23.98") || format.includes("2398"))
            return {real:23.976, display:24};


        if(format.includes("24"))
            return {real:24, display:24};


        if(format.includes("25"))
            return {real:25, display:25};


        if(format.includes("29.97") || format.includes("2997"))
            return {real:29.97, display:30};


        if(format.includes("30"))
            return {real:30, display:30};


        if(format.includes("50"))
            return {real:50, display:50};


        if(format.includes("59.94") || format.includes("5994"))
            return {real:59.94, display:60};


        if(format.includes("60"))
            return {real:60, display:60};


        return {real:30, display:30};

    }



    function tcToFrames(tc, fps){

        tc = tc.replace(";",":");


        let p = tc.split(":").map(Number);


        let h = p[0] || 0;
        let m = p[1] || 0;
        let s = p[2] || 0;
        let f = p[3] || 0;


        return Math.round(
            (
                h * 3600 +
                m * 60 +
                s
            ) * fps.real
            + f
        );

    }



    function framesToTC(frames, fps){


        let totalSeconds = Math.floor(
            frames / fps.real
        );


        let ff = Math.round(
            frames - (totalSeconds * fps.real)
        );


        let hh = Math.floor(
            totalSeconds / 3600
        );


        totalSeconds %= 3600;


        let mm = Math.floor(
            totalSeconds / 60
        );


        let ss = totalSeconds % 60;



        /*
            Prevent frame overflow
        */

        if(ff >= fps.display){

            ff = 0;
            ss++;

            if(ss >= 60){
                ss=0;
                mm++;

                if(mm>=60){
                    mm=0;
                    hh++;
                }
            }

        }



        return (
            String(hh).padStart(2,"0") + ":" +
            String(mm).padStart(2,"0") + ":" +
            String(ss).padStart(2,"0") + ":" +
            String(ff).padStart(2,"0")
        );

    }




    let rows=[];





    let runningFrames = 0;

    let masterFPS = null;
    
    let startFrames = 0;



    clips.forEach((clip,index)=>{


        let fps = getFPS(clip.format);



        /*
            Use the first clip's frame rate
            as the playlist master clock.
        */

        if(!masterFPS){
            masterFPS=fps;
        }



  let clipFrames = tcToFrames(
    clip.duration,
    fps
  );


  let inTC = framesToTC(
    startFrames,
    masterFPS || fps
  );


  runningFrames = startFrames + clipFrames;


  let outTC = framesToTC(
    runningFrames,
    masterFPS || fps
  );


  startFrames = runningFrames;



        let row=[

    String(index+1).padStart(3,"0"),


    "**:**:**:**",
    "**:**:**:**",

    "**:**:**:**",
    "**:**:**:**",

    "**:**:**:**",
    "**:**:**:**",

    "**:**:**:**",
    "**:**:**:**",


    "072",
    "128",


    "**:**:**:**",
    "**:**:**:**",
    "**:**:**:**",
    "**:**:**:**"

  ];



        switch(deck){

    case "A":

        row[1] = inTC;   // A In
        row[2] = outTC;           // A Out
       

        break;


    case "B":

        row[3] = inTC;   // B In
        row[4] = outTC;           // B Out
        
        break;


    case "C":

        row[5] = inTC;   // C In
        row[6] = outTC;           // C Out
       

        break;


    case "D":

        row[7] = inTC;   // D In
        row[8] = outTC;           // D Out
        

        break;

}


        rows.push(row);


    });



    const csv = rows
        .map(r=>r.join(","))
        .join("\r\n");



    const blob = new Blob(
        [csv],
        {
            type:"text/plain"
        }
    );


    const a=document.createElement("a");

    a.href=URL.createObjectURL(blob);

    a.download="playlist.td2";

    a.click();


    URL.revokeObjectURL(a.href);

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

fetch("hyperdeck.php?id=<?php echo $deckid;?>&cue="+clips[index].id)

.then(r=>r.text())

.then(()=>{

document.getElementById("status").innerHTML=
"Cued Clip "+clips[index].id+" - "+clips[index].name;

});

}

/////////////////////////////////////////////////////////
// Upload files to HyperDeck
/////////////////////////////////////////////////////////
async function uploadFiles(files){

    for (const file of files){

        // Ask the user for the filename
        const newName = await promptForFilename(file.name);

        // User clicked Cancel
        if (newName === null){
            continue;
        }

        document.getElementById("uploadStatus").innerHTML =
            `Uploading ${newName}...`;

        const formData = new FormData();
        formData.append("file", file);
        formData.append("filename", newName);
        formData.append("id", "<?php echo $deckid;?>");

        try{

            const response = await fetch("upload.php", {
                method: "POST",
                body: formData
            });

            const result = await response.text();

            if (!response.ok){
                throw new Error(result);
            }

            document.getElementById("uploadStatus").innerHTML =
                `${newName} uploaded`;

        }catch(e){

            document.getElementById("uploadStatus").innerHTML =
                `Upload failed: ${newName}<br>${e.message}`;

        }

    }

    loadClips();

    setTimeout(function(){
        document.getElementById("uploadStatus").innerHTML = "";
    }, 4000);

    // Clear the file picker so the same file can be selected again
    document.getElementById("fileInput").value = "";

}

/////////////////////////////////////////////////////////
// Drag and drop
/////////////////////////////////////////////////////////

const dropZone = document.getElementById("dropZone");
const fileInput = document.getElementById("fileInput");

dropZone.addEventListener("dragover", e => {
    e.preventDefault();
    dropZone.classList.add("drag");
});

dropZone.addEventListener("dragleave", () => {
    dropZone.classList.remove("drag");
});

dropZone.addEventListener("drop", e => {
    e.preventDefault();
    dropZone.classList.remove("drag");

    uploadFiles(e.dataTransfer.files);
});

// Click drop zone to open file picker
dropZone.addEventListener("click", () => {
    fileInput.click();
});

// Handle file selected from dialog
fileInput.addEventListener("change", () => {
    if (fileInput.files.length) {
        uploadFiles(fileInput.files);
    }
});

/////////////////////////////////////////////////////////
// Keyboard control
/////////////////////////////////////////////////////////
function stop(index){
fetch("hyperdeck.php?id=<?php echo $deckid;?>&stop");
}
function play(index){
fetch("hyperdeck.php?id=<?php echo $deckid;?>&play");
}

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
if(e.key==="Delete" && e.ctrlKey ){

    if(clips[selected]){
        deleteClip(clips[selected].name, e);
    }

}
});
function openPrintTable(){

    let printWindow = window.open("", "_blank");

    let table = document.getElementById("clipTable").outerHTML;

    printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
    <title>HyperDeck Clip List</title>

    <style>
        body{
            font-family:Arial,Helvetica,sans-serif;
            color:#000;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th{
            background:#ddd;
            padding:8px;
            border:1px solid #999;
            text-align:left;
        }

        td{
            padding:8px;
            border:1px solid #999;
        }
    </style>

    </head>

    <body>

    <h2><?php echo $deckname;?> Clip List</h2>

    ${table}

    </body>
    </html>
    `);

    printWindow.document.close();

}
document.getElementById("search").addEventListener("keyup",drawTable);

loadClips();
setInterval(loadClips,1000);

</script>

</body>
</html>