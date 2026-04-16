<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/tally/devicetallygrid.php?id=".$id;
header($string);
exit;
?>