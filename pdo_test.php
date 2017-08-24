<?php
include_once("session.php");
$prop = new DBConnectionProperties();
$prop->driver = "PDOMySQL";

$prop->database="store_demo";
$prop->user="store_demo";
$prop->pass="store_demo";
$prop->host="localhost";
$prop->port="3306";
$prop->setConnectionName("pdo");
$prop->is_pdo = true;
DBConnections::addConnection($prop);


DBDriver::create(true, false, "pdo");


$db = DBDriver::get();

// $res = $db->query("SELECT * FROM products");
// while ($row = $db->fetch($res)) {
//     print_r($row);
// }

$sql = "SELECT SQL_CALC_FOUND_ROWS * from products WHERE prodID=:val LIMIT 1";
$res = $db->prepare($sql);
$val = 6;
// $res->bindParam(":key", $key, PDO::PARAM_STR);
$res->bindParam(":val", $val);
$db->execute($res);

// var_dump($db->query('SELECT FOUND_ROWS();')->fetch(PDO::FETCH_COLUMN));

var_dump($db->query(" SELECT FOUND_ROWS() ")->fetchColumn());
 
$res = null;


?>
