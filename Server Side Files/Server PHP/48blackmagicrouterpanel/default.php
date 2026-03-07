<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/48blackmagicrouterpanel/?id=".$id;
header($string);
exit;
?>