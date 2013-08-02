<?php


$session_self = __FILE__;

// include_once("lib/config/path.php");
$lib_path = dirname(__FILE__)."/lib";
define ("LIB_PATH" , $lib_path);

$server_path = dirname(__FILE__);
define ("SERVER_PATH",$server_path);	

$lib_root = str_replace($_SERVER["DOCUMENT_ROOT"], "", $lib_path);
define("LIB_ROOT", $lib_root."/");

$site_root = str_replace($_SERVER["DOCUMENT_ROOT"], "", $server_path);
//site starts at this subfolder as to be accessed by HTTP - required ending slash '/'
define ("SITE_ROOT", $site_root."/");


define ("STORAGE_HREF", SITE_ROOT."storage.php");

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


define("DEBUG_OUTPUT", 1);


include_once("lib/config/defaults.php");

ini_set("include_path",".".PATH_SEPARATOR.SERVER_PATH);

include_once("lib/utils/functions.php");


include_once("config/site_config.php");



include_once("lib/dbdriver/DBConnections.php");
include_once("config/dbconfig.php");


if (!defined("SKIP_SESSION")) {
  include_once("lib/utils/Session.php");
  $session = new Session();
}



if (DB_ENABLED && !defined("SKIP_DB")) {

  include_once("lib/dbdriver/DBDriver.php");

  $g_db = DBDriver::factory();

  $g_res = $g_db->query('SELECT @@max_allowed_packet as packet_size');
  $mrow = $g_db->fetch($g_res);
		
  define("MAX_PACKET_SIZE", $mrow["packet_size"]);
  $g_db->free($g_res);

}


  if (TRANSLATOR_ENABLED && !defined("SKIP_SESSION")) {
    
    include_once("lib/utils/language.php");
  }
  else {
    include_once("lib/utils/language_notranslator.php");
  }



?>