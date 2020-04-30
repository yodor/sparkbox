<?php
//define("SKIP_SESSION",1);
define("SKIP_LANGUAGE", 1);
define("PERSISTENT_DB", 1);

include_once("session.php");
Session::Close();

include_once("lib/storage/BeanDataRequest.php");
$storage = new BeanDataRequest();
?>
