<?php


$prop = new DBConnectionProperties();
$prop->driver = "MySQL";

$prop->database="demo";
$prop->user="demo";
$prop->pass="d3m0d3m0";
$prop->host="localhost";
$prop->port="3306";



$prop->setConnectionName("default");

DBConnections::addConnection($prop);




?>