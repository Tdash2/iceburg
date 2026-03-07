<?php
include "config.php";
session_start();
if (!validateUserSession($conn, 1)) {
    showloggedout();
    exit;
}
// Require at least permission level 2
if (!function_exists('validateUserSession') || !validateUserSession($conn, 5)) {
    showAccessDenied();
    exit;
}

$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    echo "Invalid user ID";
    exit;
}

// Fetch login logs for this user
$sql = "
    SELECT id as log_id, userid, changetype, changedetails
    FROM auditlogs
    WHERE changetype = 'login'
    AND userid = ?
    ORDER BY id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $row['details_json'] = json_decode($row['changedetails'], true) ?: [];
    $logs[] = $row;
}
$stmt->close();
?>
<?php include "header.php"; ?>

<!DOCTYPE html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Audit for User #<?php echo $userId; ?></title>

    <style>
    
        #outer {
            display: flex;
            justify-content: center;
        }
            body {
             background-color: #232323;
              }
      
        .table {
            width: 80%;
            max-width: 80%;
            margin-top: 20px;
            color: #FFF;
        }
        .clickable-row {
            cursor: default; /* logs are not clickable */
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
        .table-hover tbody tr:hover {
    background-color: #2a3b53 !important; /* darker blue for hover */
    color: #fff !important;
}
        .buttonGrean { background-color: green; }
        .buttonRed { background-color: red; }
/* Apply color to the td elements directly */
.table-hover tbody tr.success td {
    background-color: #19d446 !important;  /* bright green */
    color: #fff !important;
}

.table-hover tbody tr.success:hover td {
    background-color: #19d446 !important;
}

.table-hover tbody tr.failed td {
    background-color: #ff3d4f !important;  /* bright red */
    color: #fff !important;
}

.table-hover tbody tr.failed:hover td {
    background-color: #ff3d4f !important;
}

    </style>
</head>
<body>
<br>
<center>
    <a class="buttonGrean" href="viewusers.php">Back to User</a>
</center>
<div id="outer">
    <?php if(empty($logs)): ?>
        <p>No login logs found for this user.</p>
    <?php else: ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Timestamp</th>
                    <th>IP Address</th>
                    <th>Outcome</th>
                    <th>Username Attempted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $log): 
                    $details = $log['details_json'];
                    $ts = $details['timestamp'] ?? 'unknown';
                    $ip = $details['ip'] ?? 'unknown';
                    $outcome = $details['outcome'] ?? 'unknown';
                    $username = $details['username'] ?? 'unknown';
                    $rowClass = ($outcome === 'success') ? 'success' : 'failed';
                ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo $log['log_id']; ?></td>
                        <td><?php echo $ts; ?></td>
                        <td><?php echo htmlspecialchars($ip); ?></td>
                        <td><?php echo htmlspecialchars($outcome); ?></td>
                        <td><?php echo htmlspecialchars($username); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
