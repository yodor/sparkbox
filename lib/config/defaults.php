<?php


// $base_dir  = __DIR__; // Absolute path to your installation, ex: /var/www/mywebsite
// $doc_root  = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
// $base_url  = preg_replace("!^${doc_root}!", '', $base_dir); # ex: '' or '/mywebsite'
// $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
// $port      = $_SERVER['SERVER_PORT'];
// $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
// $domain    = $_SERVER['SERVER_NAME'];
// $full_url  = "${protocol}://${domain}${disp_port}${base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc.

if (!isset($install_path)) {
  $install_path = realpath(dirname(__FILE__)."/../../");
}

//app/site deployment (server path)
define ("INSTALL_PATH", $install_path);	

//framework location  (server path)
$lib_path = $install_path."/lib";
define ("LIB_PATH" , $lib_path);

$doc_root  = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']);
$site_root = preg_replace("!^${doc_root}!", '', $install_path);
//str_replace($_SERVER["DOCUMENT_ROOT"], "", $install_path);

//app/site deployment - HTTP accessible
define ("SITE_ROOT", $site_root."/");

//framework location - HTTP accessible
$lib_root = str_replace($install_path, "", $lib_path);
define("LIB_ROOT", $lib_root."/");


define("CACHE_ROOT", realpath($doc_root."/../")."/spark_cache/");


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

$defines->set("IMAGE_UPLOAD_DEFAULT_WIDTH", 1280);
$defines->set("IMAGE_UPLOAD_DEFAULT_HEIGHT", 720);
$defines->set("IMAGE_UPLOAD_UPSCALE", false);


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
$defines->set("DEFAULT_LANGUAGE_ISO3", "eng");

$defines->set("DEFAULT_CURRENCY", "EUR");


include_once("config/defaults.php");

$defines->export();

////
//define SKIP_SESSION to skip starting session
if (!defined("SKIP_SESSION")) {
  include_once("lib/utils/Session.php");
  $session = new Session();

}


//
//define SKIP_DB to skip creating a connection to DB
if (DB_ENABLED && !defined("SKIP_DB")) {

  include_once("lib/dbdriver/DBConnections.php");
  include_once("config/dbconfig.php");
  include_once("lib/dbdriver/DBDriver.php");

  //TODO:check persistent connections with mysql. Introduced in php 5.3
  
//   if (defined("PERSISTENT_DB")) {
//   
//         try {
//             DBDriver::create(true, true, "default");
// 	}
// 	catch (Exception $e) {
//             
//             Session::set("alert", "Unable to open persistent connection to DB: ".$e->getMessage());
//             
// 	}
// 	
//   }
//   else {
  
        try {
        
            DBDriver::create();
            
        }
        catch (Exception $e) {
            Session::set("alert", "Unable to open connection to DB: ".$e->getMessage());
        }
        
//   }
  
  
//   $g_res = DBDriver::get()->query('SELECT @@max_allowed_packet as packet_size');
//   $mrow = DBDriver::get()->fetch($g_res);
//   DBDriver::get()->free($g_res);	  

//   echo $mrow["packet_size"];//33554432
  
  $defines->set("MAX_PACKET_SIZE", "33554432"); 
  $defines->export();
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
