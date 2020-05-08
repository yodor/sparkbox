<?php


// $base_dir  = __DIR__; // Absolute path to your installation, ex: /var/www/mywebsite
// $doc_root  = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
// $base_url  = preg_replace("!^${doc_root}!", '', $base_dir); # ex: '' or '/mywebsite'
// $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
// $port      = $_SERVER['SERVER_PORT'];
// $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
// $domain    = $_SERVER['SERVER_NAME'];
// $full_url  = "${protocol}://${domain}${disp_port}${base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc.

//app/site deployment (server path)
define("INSTALL_PATH", $install_path);

//framework location  (server path)
$lib_path = $install_path . "/lib";
define("LIB_PATH", $lib_path);

$doc_root = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']);
$site_root = preg_replace("!^${doc_root}!", '', $install_path);

//app/site deployment - HTTP accessible
define("SITE_ROOT", $site_root . "/");

define("SPARKFRONT", $site_root . "/sparkfront/");

//framework location - HTTP accessible
$lib_root = str_replace($install_path, "", $lib_path);
define("LIB_ROOT", $lib_root . "/");


//define("CACHE_ROOT", realpath($doc_root . "/../") . "/spark_cache/");
define("CACHE_ROOT", realpath($install_path . "/../") . "/spark_cache");

//ini_set("include_path", "." . PATH_SEPARATOR . INSTALL_PATH);
//if (isset($local_include_path)) {
//    ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . $local_include_path);
//
//}

// error_reporting(E_ALL & ~E_WARNING);
error_reporting(E_ALL);
ini_set("display_errors", "1");
ini_set("display_startup_errors", "0");
ini_set("auto_detect_line_endings", "1");
ini_set("session.cookie_lifetime", "0");
ini_set("session.use_only_cookies", "0");

ini_set("zlib.output_compression", "0");


// ini_set("session.cookie_lifetime","7200");
// ini_set("session.gc_maxlifetime","36000");

//important for storage cache to work is the timezone matching across the lamp stack, apache uses the system timezone
//mysql driver uses data.timezone to set the mysql timezone
ini_set("date.timezone", "Europe/Sofia");


//defined in .htaccess in the docroot
// <IfModule mod_php5.c>
// php_value upload_max_filesize 14000000
// php_value post_max_size 14000000
// php_value memory_limit 128000000
// </IfModule>
//httpd.conf should contain for the docroot for .htaccess to work
//<Directory "/srv/http/acw">
//  AllowOverride Options
//</Directory>

$_REQUEST = array_merge($_GET, $_POST);

include_once("utils/Globals.php");
$defines = new Globals();

include_once("utils/functions.php");

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

$defines->set("ADMIN_ROOT", SITE_ROOT . "admin/");
$defines->set("STORAGE_HREF", SITE_ROOT . "storage.php");

//'base size' for all uploaded images
$defines->set("IMAGE_UPLOAD_DEFAULT_WIDTH", 1280);
$defines->set("IMAGE_UPLOAD_DEFAULT_HEIGHT", 720);

//IMAGE_UPLOAD_DOWNSCALE = true  | uploaded images with different size from 'base size' are downscaled to size (DEFAULT_WIDTH,DEFAULT_HEIGHT)
//IMAGE_UPLOAD_DOWNSCALE = false | uploaded images are not downscaled even if dimension differ from (DEFAULT_WIDTH,DEFAULT_HEIGHT)
$defines->set("IMAGE_UPLOAD_DOWNSCALE", true);

//IMAGE_UPLOAD_UPSCALE = true  | uploaded images with different size from 'base size' are upscaled to size (DEFAULT_WIDTH,DEFAULT_HEIGHT)
//IMAGE_UPLOAD_UPSCALE = false | uploaded images are not upscaled even if dimension differ from (DEFAULT_WIDTH,DEFAULT_HEIGHT)
$defines->set("IMAGE_UPLOAD_UPSCALE", false);


$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$defines->set("SITE_PROTOCOL", $protocol);

$site_domain = $_SERVER["HTTP_HOST"];
$defines->set("SITE_DOMAIN", $site_domain);

//URL of this site without path and ending slash '/'
$defines->set("SITE_URL", $protocol . $site_domain);
$defines->set("TITLE_PATH_SEPARATOR", " :: ");
$defines->set("COOKIE_DOMAIN", $site_domain); // or .domain.com

$defines->set("DEFAULT_EMAIL_NAME", $site_domain . " Administration");
$defines->set("DEFAULT_EMAIL_ADDRESS", "info@" . $site_domain);
$defines->set("DEFAULT_SERVICE_EMAIL", "info@" . $site_domain);

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
    include_once("utils/Session.php");
    Session::Start();

}


//
//define SKIP_DB to skip creating a connection to DB
if (DB_ENABLED && !defined("SKIP_DB")) {

    debug("Setting default DBDriver");

    include_once("dbdriver/DBConnections.php");
    include_once("config/dbconfig.php");
    include_once("dbdriver/DBDriver.php");


    try {

        $driver = DBDriver::Factory(true, true, "default");
        //set default driver
        DBDriver::Set($driver);
    }
    catch (Exception $e) {

        Session::SetAlert("Unable to open persistent connection to DB: " . $e->getMessage());
    }

//
//
//    //   $g_res = DBDriver::get()->query('SELECT @@max_allowed_packet as packet_size');
//    //   $mrow = DBDriver::get()->fetch($g_res);
//    //   DBDriver::get()->free($g_res);
//
//    //   echo $mrow["packet_size"];//33554432
//
//    $defines->set("MAX_PACKET_SIZE", "33554432");
//    $defines->export();
}


if (TRANSLATOR_ENABLED && !defined("SKIP_SESSION")) {
    include_once("utils/language.php");
}
else {
    include_once("utils/language_notranslator.php");
}


// $constants = get_defined_constants(true);
// debug("Exported Globals: ",$constants["user"]);


?>
