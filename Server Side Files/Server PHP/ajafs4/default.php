<?php
$id = $_GET['id'] ?? null;

$string= "Location: http://".$_SERVER['HTTP_HOST']. "/ajafs4/index.php?id=".$id;
header($string);
exit;
?>