<?php
define("SKIP_SESSION",1);
define("SKIP_LANGUAGE",1);
include_once("session.php");
include_once("lib/utils/Storage.php");
session_write_close();
$storage = new Storage();
$storage->processRequest();
?>