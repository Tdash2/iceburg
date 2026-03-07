<?php
include "config.php";
session_start();

if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 1)) { // 2 = required permission level
    showAccessDenied();
    exit;
}
?>
<?php
include "header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iceburg Home</title>

    <style>
body {
    background-color: #232323;
}

#outer {
    display: flex;
    justify-content: center;
}

.table {
    width: 70%;
    max-width: 70%;
    color: #FFF;
}

.color-box {
    padding: 10px;
    text-align: center;
    font-weight: bold;
    border-radius: 4px;
}

.clickable-row {
    cursor: pointer;
    background-color: #313131;
}
.table-hover>tbody>tr:hover {
    background-color: #313131;
}

/* Hover effect */
table.table tbody tr.clickable-row:hover {
    background-color: #3a4f6b !important; /* row hover color */
    color: #ffffff !important; /* optional text color */
}

.buttonGrean, .buttonRed {
    display: inline-block;
    padding: 5px 10px;
    text-align: center;
    text-decoration: none;
    color: #ffffff;
    border-radius: 6px;
    outline: none;
    max-width: fit-content;
}

.buttonGrean { background-color: green; }
.buttonRed { background-color: red; }
</style>

    <script>
        $(document).ready(function($) {
            $(".clickable-row").click(function() {
                window.location = $(this).data("href");
            });
        });
    </script>
</head>
<body>
<br>
<center>


</center>
<br>
<?php
$query = "
    SELECT 
        devices.*, 
        deviceplugin.pluginName,
        deviceplugin.pluginFolder
    FROM devices 
    LEFT JOIN deviceplugin 
        ON deviceplugin.id = devices.pluginID
";



if ($result = $conn->query($query)) {
    echo '<div id="outer">';
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr>';


    echo '<th>Name</th>';
    echo '<th>Device Type</th>';
       echo '<th>Device Status</th>';

    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
    $pid = $row['pluginID']; 
    if (validateUserSession($conn, 1, $pid)) {


        $id = $row['id']; 
        $name = $row['name'] ?? 'NULL';
        $type = $row['pluginName'] ?? 'NULL';
        $folder = $row['pluginFolder'] ?? 'NULL';
        $ip = $row['ip'] ?? 'NULL';
$default = "http://" . $_SERVER['HTTP_HOST'] . $folder . "default.php?id=" . $id;

echo '<tr onclick="window.location.href=\'' . $default . '\'" style="cursor:pointer;">';


        echo '<td>' . $name . '</td>';
        echo '<td>' . $type . '</td>';
        $url="http://".$_SERVER['HTTP_HOST'].$folder."status.php?id=".$id;
        echo'<td><div class="urlDiv" data-url="'.$url.'"></div></td>';
        echo '</tr>';
    }
}
    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    $result->free();
}
?>
</body>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlDivs = document.querySelectorAll('.urlDiv');

    urlDivs.forEach(div => {
        loadDivAsync(div);
    });
});

async function loadDivAsync(div) {
    const url = div.getAttribute('data-url');
    if (!url) return;

    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.text();

        // Insert HTML content
        div.innerHTML = data;

        const timeDiv = document.createElement('div');
        timeDiv.className = 'loadTime';
        timeDiv.textContent = 'Connected at: ' + new Date().toLocaleString();

        div.insertAdjacentElement('afterbegin', timeDiv);

    } catch (err) {
        div.innerHTML = '<p style="color:red;">Error Connecting To Device</p>';

        const timeDiv = document.createElement('div');
        timeDiv.className = 'loadTime';
        timeDiv.textContent = 'Connection Failed At: ' + new Date().toLocaleString();

        div.insertAdjacentElement('afterbegin', timeDiv);
    }
}
</script>

</html>
