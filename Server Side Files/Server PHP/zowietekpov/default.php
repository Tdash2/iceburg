<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/zowietekpov/index.php?id=".$id;
header($string);
exit;
?>