<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/x32/mainmix.php?id=".$id;
header($string);
exit;
?>