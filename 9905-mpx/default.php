<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/9905-mpx/?id=".$id;
header($string);
exit;
?>