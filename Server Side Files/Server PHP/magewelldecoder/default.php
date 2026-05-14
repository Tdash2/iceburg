<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/magewelldecoder/index.php?id=".$id;
header($string);
exit;
?>