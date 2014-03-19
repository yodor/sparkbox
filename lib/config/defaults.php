<?php
define("DEBUG_OUTPUT", 1); //enable debugging output using error_log

if (!isset($install_path)) {
  $install_path = $_SERVER["DOCUMENT_ROOT"];
}

//app/site deployment (server path)
define ("INSTALL_PATH", $install_path);	

//framework location  (server path)
$lib_path = $install_path."/lib";
define ("LIB_PATH" , $lib_path);

$site_root = str_replace($_SERVER["DOCUMENT_ROOT"], "", $install_path);
//app/site deployment - HTTP accessible
define ("SITE_ROOT", $site_root."/");

//framework location - HTTP accessible
$lib_root = str_replace($install_path, "", $lib_path);
define("LIB_ROOT", $lib_root."/");



ini_set("include_path",".".PATH_SEPARATOR.INSTALL_PATH);
if (isset($local_include_path)) {
  ini_set("include_path",ini_get("include_path").PATH_SEPARATOR.$local_include_path);
  
}
include_once("lib/config/ini_setup.php");


include_once("lib/utils/Globals.php");
$defines = new Globals();

include_once("lib/utils/functions.php");

$umf = ini_get("upload_max_filesize");
KMG($umf);
$pmf = ini_get("post_max_size");
KMG($pmf);
$ml = ini_get("memory_limit");
KMG($ml);

$defines->set("UPLOAD_MAX_FILESIZE", $umf);
$defines->set("POST_MAX_FILESIZE", $pmf);
$defines->set("MEMORY_LIMIT", $ml);

$defines->set("CONTEXT_USER", "context_user");
$defines->set("CONTEXT_ADMIN", "context_admin");

$defines->set("ADMIN_ROOT", SITE_ROOT."admin/");
$defines->set("STORAGE_HREF", SITE_ROOT."storage.php");

$defines->set("IMAGE_UPLOAD_DEFAULT_WIDTH", 1024);
$defines->set("IMAGE_UPLOAD_DEFAULT_HEIGHT", 768);
$defines->set("IMAGE_UPLOAD_UPSCALE_ENABLED", false);


$site_domain = $_SERVER["HTTP_HOST"];

$defines->set("SITE_DOMAIN", $site_domain);
//URL of this site without path and ending slash '/'
$defines->set("SITE_URL","http://".$site_domain);
$defines->set("TITLE_PATH_SEPARATOR", " :: ");
$defines->set("COOKIE_DOMAIN", $site_domain); // or .domain.com

$defines->set("DEFAULT_EMAIL_NAME", $site_domain." Administration");
$defines->set("DEFAULT_EMAIL_ADDRESS", "info@".$site_domain);
$defines->set("DEFAULT_SERVICE_EMAIL", "info@".$site_domain);

$defines->set("TRANSLATOR_ENABLED", FALSE);
$defines->set("DB_ENABLED", FALSE);

$defines->set("DEFAULT_LANGUAGE", "english");
$defines->set("DEFAULT_CURRENCY", "USD");


include_once("config/defaults.php");

$defines->export();

////
//define SKIP_SESSION to skip starting session
if (!defined("SKIP_SESSION")) {
  include_once("lib/utils/Session.php");
  $session = new Session();

}

//
//define SKIP_DB to skip creating a global DB connection $g_db
if (DB_ENABLED && !defined("SKIP_DB")) {

  include_once("lib/dbdriver/DBConnections.php");
  include_once("config/dbconfig.php");
  include_once("lib/dbdriver/DBDriver.php");

  $g_db = DBDriver::factory();

  $g_res = $g_db->query('SELECT @@max_allowed_packet as packet_size');
  $mrow = $g_db->fetch($g_res);
		
  $defines->get("MAX_PACKET_SIZE", $mrow["packet_size"]);
  $g_db->free($g_res);

}


if (TRANSLATOR_ENABLED && !defined("SKIP_SESSION")) {
  include_once("lib/utils/language.php");
}
else {
  include_once("lib/utils/language_notranslator.php");
}


// $constants = get_defined_constants(true);
// debugArray("Exported Globals: ",$constants["user"]);


?>