<?php
include "../config.php";
session_start();

if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
if (!validateUserSession($conn, 3)) { // 2 = required permission level
    showAccessDenied();
    exit;
}
?>
<?php
include "../header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Devices</title>

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
    <a class="buttonGrean" href="equitment.php">Add A New Device</a>

</center>
<br>
<?php
$query = "
    SELECT 
        devices.*, 
        deviceplugin.pluginName 
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
    echo '<th>IP</th>';
    echo '<th>Delete</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        $id = $row['id']; 
        $name = $row['name'] ?? 'NULL';
        $type = $row['pluginName'] ?? 'NULL';
        $ip = $row['ip'] ?? 'NULL';

        echo '<tr class="clickable-row" data-href="equitment.php?id=' . $id . '">';
      
        echo '<td>' . $name . '</td>';
        echo '<td>' . $type . '</td>';
        echo '<td>'. $ip . '</td>';
         echo '<td><a href="deletedevice.php?id=' . $id . '" class="buttonRed" onclick="return confirm(\'Are you sure you want to delete this device?\');">Delete</a></td>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    $result->free();
}
?>
</body>
</html>
