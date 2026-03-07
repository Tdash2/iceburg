<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/blackmagicvideohub/bmdrouterroutes.php?id=".$id;
header($string);
exit;
?>