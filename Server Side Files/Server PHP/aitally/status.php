<?php
include "../config.php";
session_start();
if (!validateUserSession($conn, 1)) { showloggedout(); exit; }
if (!validateUserSession($conn, 1, $_GET['id'])) { showAccessDenied(); exit; }


echo "Iceburg Tally AI Speach To Tally";
$id= $_GET['id'];
$server = $_SERVER['HTTP_HOST'];
echo '<br>';
echo '<a href="https://'.$server.'/aitally/?id='. $id.'">Click Here to Launtch Client</a>';
?>

