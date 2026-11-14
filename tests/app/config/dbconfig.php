<?php
$c = include(APP_PATH . "/../config.php");
$dbconfig = new DBConfig();
$dbconfig->host = $c["db_host"];
$dbconfig->port = $c["db_port"];
$dbconfig->database = $c["db_name"];
$dbconfig->user = $c["db_user"];
$dbconfig->pass = $c["db_pass"];
DBManager::Add($dbconfig);
