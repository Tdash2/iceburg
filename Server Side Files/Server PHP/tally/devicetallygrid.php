<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, $_GET['id'])) { showAccessDenied(); exit; }


if (!isset($_GET['id'])) {
    die ("No ID");
}
$deviceIdurl = intval($_GET['id']);

/* ---------- DEVICES ---------- */
$devices2 = [];
$res = $conn->query("SELECT id, name FROM devices WHERE pluginID = 4 OR pluginID = 7 ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $devices2[] = $row;
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

foreach ($devices2 as $d) {

    // ALL sources (inputs)
    if (!empty($inputChannels[$d['id']])) {
        $inputGroups[] = [
            "id"=>$d['id'],
            "name"=>$d['name'],
            "channels"=>$inputChannels[$d['id']]
        ];
    }

    // ONLY destination device 77
    if ($d['id'] == $deviceIdurl && !empty($outputChannels[$d['id']])) {
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
var DEVICES = <?php echo json_encode(array_column($devices2,'id')); ?>;
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
        let colIndex = 2;

        // ---------- TOTAL INPUT COLUMNS ----------
        let totalCols = 0;
        INPUT_GROUPS.forEach(inGroup => {
            totalCols += 1;
            if (!collapsedInputs[inGroup.id]) {
                totalCols += Object.keys(inGroup.channels).length;
            }
        });

        // ---------- TOTAL OUTPUT ROWS ----------
        let totalRows = 1;
        OUTPUT_GROUPS.forEach(outGroup => {
            totalRows += 1;
            if (!collapsedOutputs[outGroup.id]) {
                totalRows += Object.keys(outGroup.channels).length;
            }
        });

        // ---------- CORNER ----------
        const corner = document.createElement("div");
        corner.className = "rowheader low corner";
        corner.style.gridColumnStart = 1;
        corner.style.gridRowStart = 1;
        grid.appendChild(corner);

        // =========================================================
        // ?? INPUTS (TOP)
        // =========================================================
        colIndex = 2;

        INPUT_GROUPS.forEach(inGroup => {
            const chKeys = Object.keys(inGroup.channels);

            const deviceHeader = document.createElement("div");
            deviceHeader.className = "header group rotate device-header";
            deviceHeader.id = "in_device_" + inGroup.id;

            deviceHeader.innerHTML =
                `<i class="fa-solid fa-chevron-down fa-toggle-icon${
                    collapsedInputs[inGroup.id] ? ' collapsed' : ''
                }"></i> ${inGroup.name}`;

            deviceHeader.style.gridColumnStart = colIndex;
            deviceHeader.style.gridRowStart = 1;

            deviceHeader.onclick = () => {
                collapsedInputs[inGroup.id] = !collapsedInputs[inGroup.id];
                buildGrid();
            };

            grid.appendChild(deviceHeader);

            // vertical fill
            const vfill = document.createElement("div");
            vfill.className = "group-fill-vert";
            vfill.style.gridColumnStart = colIndex;
            vfill.style.gridRow = "2 / " + (1 + totalRows);
            grid.appendChild(vfill);

            let chCol = colIndex + 1;

            if (!collapsedInputs[inGroup.id]) {
                chKeys.forEach(ch => {
                    const chHeader = document.createElement("div");
                    chHeader.className = "header rotate low";
                    chHeader.textContent = inGroup.channels[ch];
                    chHeader.id = `in_${inGroup.id}_${ch}`;

                    chHeader.style.gridColumnStart = chCol;
                    chHeader.style.gridRowStart = 1;

                    grid.appendChild(chHeader);
                    chCol++;
                });
            }

            inGroup._startCol = colIndex;
            colIndex += 1 + (collapsedInputs[inGroup.id] ? 0 : chKeys.length);
        });

        // =========================================================
        // ?? OUTPUTS (LEFT)
        // =========================================================
        rowIndex = 2;

        OUTPUT_GROUPS.forEach(outGroup => {
            const chKeys = Object.keys(outGroup.channels);

            const deviceRow = document.createElement("div");
            deviceRow.className = "rowheader group";
            deviceRow.id = "out_device_" + outGroup.id;

            deviceRow.innerHTML =
                `<i class="fa-solid fa-chevron-down fa-toggle-icon3${
                    collapsedOutputs[outGroup.id] ? ' collapsed' : ''
                }"></i> ${outGroup.name}`;

            deviceRow.style.gridColumnStart = 1;
            deviceRow.style.gridRowStart = rowIndex;

            deviceRow.onclick = () => {
                collapsedOutputs[outGroup.id] = !collapsedOutputs[outGroup.id];
                buildGrid();
            };

            grid.appendChild(deviceRow);

            // horizontal fill
            const hfill = document.createElement("div");
            hfill.className = "group-fill";
            hfill.style.gridColumn = "2 / " + (2 + totalCols);
            hfill.style.gridRowStart = rowIndex;
            grid.appendChild(hfill);

            rowIndex++;

            if (!collapsedOutputs[outGroup.id]) {
                chKeys.forEach(outCh => {

                    const rowh = document.createElement("div");
                    rowh.className = "rowheader low";
                    rowh.textContent = outGroup.channels[outCh];
                    rowh.id = `out_${outGroup.id}_${outCh}`;

                    rowh.style.gridColumnStart = 1;
                    rowh.style.gridRowStart = rowIndex;

                    grid.appendChild(rowh);

                    // cells
                    INPUT_GROUPS.forEach(inGroup => {
                        if (!collapsedInputs[inGroup.id]) {
                            const inChKeys = Object.keys(inGroup.channels);

                            inChKeys.forEach((inCh, idx) => {
                                const cell = document.createElement("div");
                                cell.className = "cell";

                                cell.dataset.indev = inGroup.id;
                                cell.dataset.inch = inCh;
                                cell.dataset.outdev = outGroup.id;
                                cell.dataset.outch = outCh;

                                if (
                                    MAPS?.[inGroup.id]?.[inCh]?.[outGroup.id]?.[outCh]
                                ) {
                                    cell.classList.add("mapped");
                                }

                                cell.style.gridColumnStart =
                                    inGroup._startCol + 1 + idx;

                                cell.style.gridRowStart = rowIndex;

                                grid.appendChild(cell);
                            });
                        }
                    });

                    rowIndex++;
                });
            }
        });

        grid.style.gridTemplateColumns =
            rowHeaderWidth +
            "px repeat(" +
            totalCols +
            ", " +
            cellSize +
            "px)";

        enableInputToggles();
    }

    // =========================================================
    // CLICK MAP
    // =========================================================
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

    // =========================================================
    // INPUT TOGGLE
    // =========================================================
    function enableInputToggles() {
        INPUT_GROUPS.forEach(inGroup => {
            Object.keys(inGroup.channels).forEach(inCh => {
                const el = document.getElementById(`in_${inGroup.id}_${inCh}`);
                if (!el) return;

                el.onclick = async function () {
                    const isActive = this.classList.contains("high");
                    const newVal = isActive ? 0 : 1;

                    const url = `/tally/settallystatus.php?id=${inGroup.id}&ch${inCh}=${newVal}`;
                    const res = await fetch(url);
                    if (!res.ok) return;

                    this.classList.toggle("high", !isActive);
                    this.classList.toggle("low", isActive);
                };
            });
        });
    }

    // =========================================================
    // DEVICE STATUS (ONLINE)
    // =========================================================
    function refreshDeviceStatus() {
        DEVICES.forEach(id => {
            fetch("/tally/devicestatus.php?id=" + id)
            .then(r => r.text())
            .then(status => {

                const online = status.trim() === "true";

                const inDev = document.getElementById("in_device_" + id);
                const outDev = document.getElementById("out_device_" + id);

                if (inDev) inDev.classList.toggle("device-online", online);
                if (outDev) outDev.classList.toggle("device-online", online);

            })
            .catch(()=>{});
        });
    }

    // =========================================================
    // STATUS REFRESH (INPUT + OUTPUT)
    // =========================================================
    function refreshStatus() {
        DEVICES.forEach(id => {
            fetch("gettallystatus.php?id=" + id)
                .then(r => r.json())
                .then(s => {
                    if (!s) return;

                    // INPUTS (top)
                    if (s.inputs) {
                        Object.entries(s.inputs).forEach(([ch, val]) => {
                            const el = document.getElementById(`in_${id}_${ch}`);
                            if (el) {
                                el.classList.toggle("high", !!val);
                                el.classList.toggle("low", !val);
                            }
                        });
                    }

                    // OUTPUTS (left rows now!)
                    if (s.outputs) {
                        Object.entries(s.outputs).forEach(([ch, val]) => {
                            const el = document.getElementById(`out_${id}_${ch}`);
                            if (el) {
                                el.classList.toggle("high", !!val);
                                el.classList.toggle("low", !val);
                            }
                        });
                    }

                })
                .catch(()=>{});
        });
    }

    // =========================================================
    // MAPPING REFRESH
    // =========================================================
    function refreshTallyMappings() {
        fetch('gettallymappings.php')
            .then(r => r.json())
            .then(mappings => {

                document.querySelectorAll('.cell.mapped')
                    .forEach(c => c.classList.remove('mapped'));

                mappings.forEach(m => {
                    const sel = `.cell[data-indev="${m.from_device}"][data-inch="${m.from_channel}"][data-outdev="${m.to_device}"][data-outch="${m.to_channel}"]`;
                    const el = document.querySelector(sel);
                    if (el) el.classList.add('mapped');
                });

            })
            .catch(()=>{});
    }

    // =========================================================
    // INIT
    // =========================================================
    buildGrid();
    refreshStatus();
    refreshDeviceStatus();
    refreshTallyMappings();

    setInterval(refreshStatus, 500);
    setInterval(refreshDeviceStatus, 5000);
    setInterval(refreshTallyMappings, 800);

});
</script>

</body>
</html>
