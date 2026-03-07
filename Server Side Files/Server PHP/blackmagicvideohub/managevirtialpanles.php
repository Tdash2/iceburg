<?php
include "../config.php";
session_start();


// Check permissions
if (!validateUserSession($conn, 0)) {
    showloggedout();
    exit;
}
// Check permissions
if (!validateUserSession($conn, 1, 2)) {
    showAccessDenied();
    exit;
}

include "../header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Router Panle</title>

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

// Get deviceID from GET, if provided, and ensure it's an integer
$deviceID = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Current user ID from session
$currentUserID = isset($_SESSION['Userid']) ? $_SESSION['Userid'] : null;

if ($currentUserID === null) {
    echo "User not logged in.";
    exit;
}

// Prepare statement with filter
$stmt = $conn->prepare("
    SELECT 
        routerpanle.panleName,
        routerpanle.id,
        routerpanle.allowedusers,
        routerpanle.deviceID,
        devices.name AS deviceName
    FROM routerpanle
    LEFT JOIN devices 
        ON routerpanle.deviceID = devices.id
    WHERE routerpanle.deviceID = ?
");
$stmt->bind_param("i", $deviceID);

$stmt->execute();
$result = $stmt->get_result();

$userperms = $_SESSION['user_permissions'] ?? 0;

if ($userperms > 3) {
    echo '<br>';
    echo '<center>';
    echo '<a class="buttonGrean" href="editpanle.php?return=' . $deviceID . '">Add A New Virtual Panle</a>';
    echo '</center>';
    echo '<br>';
}

echo '<div id="outer">';
echo '<table class="table table-hover">';
echo '<thead>';
echo '<tr>';
echo '<th>Name</th>';
echo '<th>Device Type</th>';
echo '<th>Device Status</th>';
if ($userperms > 3) {
    echo '<th>Edit Panle</th>';
}
if ($userperms > 3) {
    echo '<th>Delete Panle</th>';
}
echo '</tr>';
echo '</thead>';
echo '<tbody>';

while ($row = $result->fetch_assoc()) {
    $allowedUsers = json_decode($row['allowedusers'], true); // decode JSON to array

if ($userperms < 4) {
    // Only show row if current user ID is in allowedUsers
    if (!in_array($currentUserID, $allowedUsers)) {
        continue; // skip this row
    }
}
    $id = $row['id']; 
    $name = $row['panleName'] ?? 'NULL';
    $type = $row['deviceName'] ?? 'NULL';
    $devicename = $row['deviceName'] ?? 'NULL';
    $devicenamed = $row['deviceID'] ?? 'NULL';

    echo '<tr class="clickable-row" data-href="virtialrouterpanle.php?id=' . $id. '">';
    echo '<td>' . $name . '</td>';
    echo '<td>' . $type . '</td>';
    echo '<td>' . $devicename . '</td>';

    if ($userperms > 3) {
        echo '<td>
                <a href="editpanle.php?id=' . $id .  '&return=' . $deviceID . '" class="buttonGrean">Edit Panle</a>
              </td>';
    }
    if ($userperms > 3) {
        echo '<td>
                <a href="deletepanel.php?id=' . $id .  '&rtrid=' . $deviceID . '" class="buttonred">Delete Panel</a>
              </td>';
    }

    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';

$stmt->close();
?>


</body>
<script>
// Automatically fetch data for all divs with data-url
document.addEventListener('DOMContentLoaded', () => {
    const urlDivs = document.querySelectorAll('.urlDiv');
    urlDivs.forEach(div => {
        const url = div.getAttribute('data-url');
        if (!url) return;

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(data => {
                // Insert HTML content
                div.innerHTML = data;

                // Show load finished time
                const timeDiv = document.createElement('div');
                timeDiv.className = 'loadTime';
                timeDiv.textContent = 'Connected at: ' + new Date().toLocaleString();

                // Add it at the top
                div.insertAdjacentElement('afterbegin', timeDiv);
            })
            .catch(err => {
                div.innerHTML = '<p style="color:red;">Error Connecting To Device</p>';

                const timeDiv = document.createElement('div');
                timeDiv.className = 'loadTime';
                timeDiv.textContent = 'Connection Failed At: ' + new Date().toLocaleString();
                div.insertAdjacentElement('afterbegin', timeDiv);
            });
    });
});
</script>
</html>
