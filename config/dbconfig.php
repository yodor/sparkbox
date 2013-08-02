<?php


$prop = new DBConnectionProperties();
$prop->driver = "MySQL";

$prop->database="demo";
$prop->user="demo";
$prop->pass="d3m0";
$prop->host="127.0.0.1";
$prop->port="3306";



$prop->setConnectionName("default");

DBConnections::addConnection($prop);




?>