<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/bmdSmartScope/?id=".$id;
header($string);
exit;
?>