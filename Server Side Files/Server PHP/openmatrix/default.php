<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/openmatrix/partylines.php?id=".$id;
header($string);
exit;
?>