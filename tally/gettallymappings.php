<?php
include "../config.php";
session_start();



if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, 4)) { showAccessDenied(); exit; }




if (!validateUserSession($conn, 1)) { showloggedout(); exit; }



$res = $conn->query("SELECT from_device, from_channel, to_device, to_channel FROM tally_mappings");
$mappings = [];
while($row = $res->fetch_assoc()){
    $mappings[] = $row;
}
echo json_encode($mappings);
