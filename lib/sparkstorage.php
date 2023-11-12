<?php
define("STORAGE_REQUEST", 1);
Session::Close();

include_once("storage/BeanDataRequest.php");
$storage = new BeanDataRequest();
?>
