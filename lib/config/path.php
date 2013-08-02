<?php
$lib_path = dirname(dirname(__FILE__));
define ("LIB_PATH" , $lib_path);

$server_path = dirname(dirname(dirname(__FILE__)));
define ("SERVER_PATH",$server_path);	

$lib_root = str_replace($_SERVER["DOCUMENT_ROOT"], "", $lib_path);
define("LIB_ROOT", $lib_root."/");

$site_root = str_replace($_SERVER["DOCUMENT_ROOT"], "", $server_path);
//site starts at this subfolder as to be accessed by HTTP - required ending slash '/'
define ("SITE_ROOT", $site_root."/");


define ("STORAGE_HREF",SITE_ROOT."storage.php");

if (defined("DEBUG_PATHS")) {
  echo "Lib path: $lib_path";
  echo "<hr>";

  echo "Server path: $server_path";
  echo "<hr>";

  echo "Lib root: $lib_root";
  echo "<hr>";

  echo "Site root: $site_root";
  echo "<HR>";

  echo "Storage href: ".STORAGE_HREF;
}





?>
