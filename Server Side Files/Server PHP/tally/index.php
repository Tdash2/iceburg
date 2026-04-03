<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 4)) { showAccessDenied(); exit; }

/* ---------- DEVICES ---------- */
$devices = [];
$res = $conn->query("SELECT id, name FROM devices WHERE pluginID = 4 OR pluginID = 7 ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $devices[] = $row;
}

/* ---------- MAPPINGS ---------- */
$mapLookup = [];
$res2 = $conn->query("SELECT * FROM tally_mappings");
while ($m = $res2->fetch_assoc()) {
    $mapLookup[$m['from_device']][$m['from_channel']]
              [$m['to_device']][$m['to_channel']] = true;
}

/* ---------- CHANNELS ---------- */
$inputChannels = [];
$outputChannels = [];

$res3 = $conn->query("SELECT * FROM tally_channels");
while ($row = $res3->fetch_assoc()) {
    $deviceId = $row['device_id'];
    $ch       = (string)$row['channel'];
    $name     = $row['name'];
    $type     = $row['type'] ?? 'input';

    if ($type === 'input')
        $inputChannels[$deviceId][$ch] = $name;
    else
        $outputChannels[$deviceId][$ch] = $name;
}

/* ---------- GROUP STRUCTURE ---------- */
$inputGroups = [];
$outputGroups = [];

foreach ($devices as $d) {

    if (!empty($inputChannels[$d['id']])) {
        $inputGroups[] = [
            "id"=>$d['id'],
            "name"=>$d['name'],
            "channels"=>$inputChannels[$d['id']]
        ];
    }

    if (!empty($outputChannels[$d['id']])) {
        $outputGroups[] = [
            "id"=>$d['id'],
            "name"=>$d['name'],
            "channels"=>$outputChannels[$d['id']]
        ];
    }
}
?>

<?php include "../header.php"; ?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Tally Routing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body{
    margin:0;
    background:#232323;
    color:#eee;
    font-family:Arial;
}


h2{padding:8px;margin:0;}

.grid2-container {
    width: 95%;

    background: #232323;
  margin-left: auto;
  margin-right: auto;
}

/* Make the scrollable container the sticky reference */
.grid-container {
    width: 95%;
    height: 85%; /* full viewport minus header height */
    overflow-x: auto;  /* horizontal scroll */
    overflow-y: auto;  /* vertical scroll inside the grid only */
    background: #232323;
     max-width: fit-content;

}
.rowheader.low.corner {
    position: sticky;
    top: 0;
    left: 0;
    z-index: 11; /* above all other headers */
}

.grid-wrapper {
position: relative; /* keep for absolute positioning of corner */
    display: inline-block;  /* prevents grid shrinkage */
    position: relative;
}
/* Top-left corner wrapper */
.grid-wrapper::before {
    content: "";
    position: sticky;
    top: 0;
    left: 0;
    width: 150px;        /* match rowheader width */
    height: 26px;        /* match header height */
    z-index: 6;
    background: #1d1d1d;
    pointer-events: none; /* so clicks go through */
}

/* Top-left corner cell */
.header:first-child {
    position: sticky;
    top: 0;           /* stick to top when scrolling vertically */
    left: auto;       /* allow horizontal scrolling */
    z-index: 6;       /* above other headers */
    background: #1d1d1d;
    min-width: 150px; /* match row header width */
}


#grid {
    display: grid;
    grid-auto-rows: minmax(26px, auto);
    min-width: calc(150px + 26px * 10); /* 120px for row header + estimated output columns */
}



.header{
    position:sticky;
    top:0;
    background:#1d1d1d;
    border:1px solid #444;
    text-align:center;
    font-size:12px;
    z-index:5;
}

.rowheader {
    position: sticky;
    left: 0;
    background: #171616;
    border: 1px solid #444;
    padding: 6px;
    font-size: 12px;
    display: flex;       /* keep flex for icon + label */
    align-items: center;
    line-height: normal; /* let content dictate height */
    min-height: 26px;    /* minimum row height */
    box-sizing: border-box;
    z-index: 4;
}



.group{
    font-weight:bold;
    cursor:pointer;
}

.cell{
    border:1px solid #333;
    cursor:pointer;
}

.mapped{ background:#ff9900; }
.high{ background:#770000; }
.low{ background:#232323; }
.header, .rowheader, .cell {
    display: flex;
    justify-content: center;
    align-items: center;
    box-sizing: border-box;
}
.header.group {
    font-weight: bold;
    cursor: pointer;
    background: #1d1d1d;
    border-bottom: 1px solid #555; 
    height: 26px; 
    line-height: 26px; 
}
.rowheader.group {
    font-weight: bold;
    cursor: pointer;
    /* override fixed flex issues */
    height: auto;
}


.header.rotate {
    writing-mode: sideways-lr; 
    white-space: nowrap;
    font-size: 12px;
    padding: 4px 2px;
    min-height: 150px;
    line-height: normal;
    display: flex;
    justify-content: center;
    box-sizing: border-box; 
}
.header.high {
    background: #770000 !important;
    color: #fff !important;
}
.header.low {
    color: #eee !important;
}
.header.device-header {
    background: #171616;
    color: #fff;
}
.fa-toggle-icon {
  display: inline-block;
  transition: transform 0.2s ease;
      margin-top: 4px;
}

.fa-toggle-icon3 {
  display: inline-block;
  transition: transform 0.2s ease;
      margin-right: 4px;
}

.fa-toggle-icon3.collapsed {
  transform: rotate(-90deg);
}
.group-fill {
    background: #171616;
    border-bottom: 1px solid #555;
}

.group-fill-vert {
    background: #171616;
    border-right: 1px solid #555;
}

.device-online {
    background: #0a7d12 !important;
    color: #fff !important;
}

.grid-corner {
    position: absolute;    /* lock position relative to grid-wrapper */
    top: 0;
    left: 0;
    width: 150px;          /* match your row header width */
    height: 26px;          /* match header height */
    background: #1d1d1d;   /* same as headers */
    border: 1px solid #444;
    z-index: 10;           /* above everything */
    pointer-events: none;  /* let clicks pass through */
}

</style>
</head>

<body>


<div class="grid2-container">
<h2>Tally Routing</h2>
<div class="grid-container">
  <div class="grid-wrapper">
    <div id="grid"></div>

  </div>
  </div>

<script>
var INPUT_GROUPS = <?php echo json_encode($inputGroups); ?>;
var OUTPUT_GROUPS = <?php echo json_encode($outputGroups); ?>;
var MAPS = <?php echo json_encode($mapLookup); ?>;
var DEVICES = <?php echo json_encode(array_column($devices,'id')); ?>;
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const grid = document.getElementById("grid");
    const collapsedInputs = {};
    const collapsedOutputs = {};

function buildGrid() {
    grid.innerHTML = "";

    const rowHeaderWidth = 150;
    const cellSize = 26;
    let rowIndex = 1;

    // ---------- TOTAL OUTPUT COLUMNS ----------
    // ---------- TOTAL OUTPUT COLUMNS ----------
let totalCols = 0;
OUTPUT_GROUPS.forEach(outGroup => {
    totalCols += 1; // device header always counts
    if (!collapsedOutputs[outGroup.id]) {
        totalCols += Object.keys(outGroup.channels).length; // only add channels if expanded
    }
});

    // ---------- ESTIMATE TOTAL ROWS ----------
    let totalRows = 1; // header row
    INPUT_GROUPS.forEach(inGroup => {
        totalRows++; // device row
        if (!collapsedInputs[inGroup.id]) {
            totalRows += Object.keys(inGroup.channels).length;
        }
    });

    // ---------- TOP-LEFT CORNER ----------
const corner = document.createElement("div");
corner.className = "rowheader low corner";
corner.textContent = " "; // or your corner label
corner.style.gridColumnStart = 1;
corner.style.gridRowStart = 1;
grid.appendChild(corner);

    // ---------- OUTPUT DEVICES ----------
// ---------- OUTPUT DEVICES ----------
let colIndex = 2;


OUTPUT_GROUPS.forEach(outGroup => {
    const outChKeys = Object.keys(outGroup.channels);

    // Device header (name) — always visible
    const deviceHeader = document.createElement("div");
    deviceHeader.className = "header group rotate device-header";
    deviceHeader.id = "outlabel_" + outGroup.id;
    deviceHeader.innerHTML =
        `<i class="fa-solid fa-chevron-down fa-toggle-icon${
            collapsedOutputs[outGroup.id] ? ' collapsed' : ''
        }"></i> ${outGroup.name}`;

    deviceHeader.style.gridColumnStart = colIndex;
    deviceHeader.style.gridRowStart = rowIndex;
    deviceHeader.style.cursor = "pointer";

    deviceHeader.onclick = () => {
        collapsedOutputs[outGroup.id] = !collapsedOutputs[outGroup.id];
        buildGrid();
    };

    grid.appendChild(deviceHeader);

    // ALWAYS render vertical filler for device column
    const vfill = document.createElement("div");
    vfill.className = "group-fill-vert";
    vfill.style.gridColumnStart = colIndex;
    vfill.style.gridRow = (rowIndex + 2) + " / span " + (totalRows - rowIndex - 1);
    grid.appendChild(vfill);

    // Only render channels if NOT collapsed
    if (!collapsedOutputs[outGroup.id]) {

        let chCol = colIndex + 1;

        outChKeys.forEach(ch => {
            const chHeader = document.createElement("div");
            chHeader.className = "header rotate low";
            chHeader.textContent = outGroup.channels[ch];
            chHeader.id = `outlabel_${outGroup.id}_${ch}`;

            chHeader.style.gridColumnStart = chCol;
            chHeader.style.gridRowStart = rowIndex;

            grid.appendChild(chHeader);
            chCol++;
        });

    }

    outGroup._startCol = colIndex;
    colIndex += 1 + (collapsedOutputs[outGroup.id] ? 0 : outChKeys.length);
});



    rowIndex++;

    // ---------- INPUT DEVICES ----------
    INPUT_GROUPS.forEach(inGroup => {
        const inChKeys = Object.keys(inGroup.channels);

        // Left device label
        const deviceRow = document.createElement("div");
deviceRow.className = "rowheader group";
deviceRow.id = "in_device_" + inGroup.id;

deviceRow.innerHTML =
            `<i class="fa-solid fa-chevron-down fa-toggle-icon3${
                collapsedInputs[inGroup.id] ? ' collapsed' : ''
            }"></i> ${inGroup.name}`;

        deviceRow.style.gridColumnStart = 1;
        deviceRow.style.gridRowStart = rowIndex;

        deviceRow.onclick = () => {
            collapsedInputs[inGroup.id] =
                !collapsedInputs[inGroup.id];
            buildGrid();
        };

        grid.appendChild(deviceRow);

        // horizontal divider fill
        const filler = document.createElement("div");
        filler.className = "group-fill";
        filler.style.gridColumn = "2 / span " + totalCols;
        filler.style.gridRowStart = rowIndex;
        grid.appendChild(filler);

        rowIndex++;

        if (!collapsedInputs[inGroup.id]) {
            inChKeys.forEach(inCh => {
                const rowh = document.createElement("div");
                rowh.className = "rowheader low";
                rowh.id = "in_" + inGroup.id + "_" + inCh;
                rowh.textContent =
                    inGroup.channels[inCh];

                rowh.style.gridColumnStart = 1;
                rowh.style.gridRowStart = rowIndex;
                grid.appendChild(rowh);

                OUTPUT_GROUPS.forEach(outGroup => {
                    if (!collapsedOutputs[outGroup.id]) {
                        const outChKeys =
                            Object.keys(outGroup.channels);

                        outChKeys.forEach((outCh, idx) => {
                            const cell =
                                document.createElement("div");

                            cell.className = "cell";
                            cell.dataset.indev =
                                inGroup.id;
                            cell.dataset.inch = inCh;
                            cell.dataset.outdev =
                                outGroup.id;
                            cell.dataset.outch =
                                outCh;

                            if (
                                MAPS?.[
                                    inGroup.id
                                ]?.[inCh]?.[
                                    outGroup.id
                                ]?.[outCh]
                            ) {
                                cell.classList.add(
                                    "mapped"
                                );
                            }

                            cell.style.gridColumnStart =
                                outGroup._startCol +
                                1 +
                                idx;

                            cell.style.gridRowStart =
                                rowIndex;

                            grid.appendChild(cell);
                        });
                    }
                });

                rowIndex++;
            });
        }
    });

    // ---------- GRID TEMPLATE ----------
    grid.style.gridTemplateColumns =
        rowHeaderWidth +
        "px repeat(" +
        totalCols +
        ", " +
        cellSize +
        "px)";

    enableInputToggles();
}




    // ---------- CLICK MAPPING ----------
    grid.addEventListener("click", function (e) {
        const cell = e.target.closest(".cell");
        if (!cell) return;

        const d = cell.dataset;
        const mapped = cell.classList.contains("mapped");

        const url = mapped
            ? `removemapping.php?from_device=${d.indev}&from_ch=${d.inch}&to_device=${d.outdev}&to_ch=${d.outch}`
            : `addmapping.php?from_device=${d.indev}&from_ch=${d.inch}&to_device=${d.outdev}&to_ch=${d.outch}`;

        fetch(url).then(res => {
            if (res.ok) cell.classList.toggle("mapped");
        });
    });

    // ---------- INPUT TOGGLE HANDLER ----------
    function enableInputToggles() {
        INPUT_GROUPS.forEach(inGroup => {
            Object.keys(inGroup.channels).forEach(inCh => {
                const el = document.getElementById(`in_${inGroup.id}_${inCh}`);
                if (!el) return;

                el.replaceWith(el.cloneNode(true));
                const newEl = document.getElementById(`in_${inGroup.id}_${inCh}`);

                newEl.addEventListener("click", async function () {
                    try {
                        const isActive = this.classList.contains("high");
                        const newVal = isActive ? 0 : 1;

                        const url = `/tally/settallystatus.php?id=${encodeURIComponent(inGroup.id)}&ch${encodeURIComponent(inCh)}=${newVal}`;
                        const res = await fetch(url, { method: 'GET' });

                        if (!res.ok) {
                            console.error("Tally update failed", res.status, url);
                            return;
                        }

                        this.classList.toggle("high", !isActive);
                        this.classList.toggle("low", isActive);

                    } catch (err) {
                        console.error("Error toggling input tally", err);
                    }
                });
            });
        });
    }

    // ---------- STATUS REFRESH ----------
    function safeFetchJson(url, timeout = 3000) {
        return Promise.race([
            fetch(url, { cache: "no-store" }).then(r => r.json()),
            new Promise((_, rej) => setTimeout(() => rej("timeout"), timeout))
        ]);
    }

function refreshDeviceStatus() {
    DEVICES.forEach(id => {

        fetch("/tally/devicestatus.php?id=" + id, { cache: "no-store" })
        .then(r => r.text())
        .then(status => {

            const online = status.trim() === "true";

            const inDev = document.getElementById("in_device_" + id);
            const outDev = document.getElementById("outlabel_" + id);

            if (inDev) {
                inDev.classList.toggle("device-online", online);
            }

            if (outDev) {
                outDev.classList.toggle("device-online", online);
            }

        })
        .catch(()=>{});
    });
}

    function refreshStatus() {
        DEVICES.forEach(id => {
            safeFetchJson("gettallystatus.php?id=" + id)
                .then(s => {
                    if (!s) return;

                    if (s.inputs) {
                        Object.entries(s.inputs).forEach(([ch, val]) => {
                            const el = document.getElementById("in_" + id + "_" + ch);
                            if (el) {
                                const status = !!val;
                                el.classList.toggle("high", status);
                                el.classList.toggle("low", !status);
                            }
                        });
                    }

                    if (s.outputs) {
                        Object.entries(s.outputs).forEach(([ch, val]) => {
                            const headerEl = document.getElementById(`outlabel_${id}_${ch}`);
                            if (headerEl) {
                                const status = !!val;
                                headerEl.classList.toggle("high", status);
                                headerEl.classList.toggle("low", !status);
                            }
                        });
                    }

                })
                .catch(() => {});
        });
    }
async function refreshTallyMappings() {
    try {
        // Fetch the current mappings
        const mappings = await safeFetchJson('gettallymappings.php', 4000);

        // Clear all existing mapped classes
        document.querySelectorAll('.cell.mapped').forEach(cell => cell.classList.remove('mapped'));

        // Apply the new mappings
        if (Array.isArray(mappings)) {
            mappings.forEach(m => {
                const selector = `.cell[data-indev="${CSS.escape(String(m.from_device))}"][data-inch="${CSS.escape(String(m.from_channel))}"][data-outdev="${CSS.escape(String(m.to_device))}"][data-outch="${CSS.escape(String(m.to_channel))}"]`;
                const el = document.querySelector(selector);
                if (el) el.classList.add('mapped');
            });
        } else {
            console.warn('gettallymappings returned unexpected data:', mappings);
        }
    } catch (err) {
        console.error('Failed to refresh tally mappings:', err);
    }
}


    // ---------- INITIAL BUILD ----------
    buildGrid();
    refreshStatus();
    setInterval(refreshStatus, 400);
refreshTallyMappings(); // one-time update
setInterval(refreshTallyMappings, 400); // auto-refresh every second
refreshDeviceStatus();
setInterval(refreshDeviceStatus, 5000);
});
</script>

</body>
</html>
