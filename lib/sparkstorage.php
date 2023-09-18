<?php
define("STORAGE_REQUEST", 1);
$GLOBALS["DEBUG_OUTPUT"] = 0;

Session::Close();

include_once("storage/BeanDataRequest.php");
$storage = new BeanDataRequest();
?>