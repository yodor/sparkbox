<?php

// $base_dir  = __DIR__; // Absolute path to your installation, ex: /var/www/mywebsite
// $doc_root  = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
// $base_url  = preg_replace("!^${doc_root}!", '', $base_dir); # ex: '' or '/mywebsite'
// $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
// $port      = $_SERVER['SERVER_PORT'];
// $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
// $domain    = $_SERVER['SERVER_NAME'];
// $full_url  = "${protocol}://${domain}${disp_port}${base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc.

if (!$install_path) throw new Exception("Install path is not defined");

if (defined("REQUEST_THROTTLE_USERAGENT")) {
    include_once("ratelimit.php");
}


if (isset($GLOBALS["FORCE_HTTPS"]) && $GLOBALS["FORCE_HTTPS"]==0) {

}
else {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
        $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/2 301 Moved Permanently');
        header('Location: ' . $location);
        exit;
    }
}

include_once("utils/SparkGlobals.php");
$defines = SparkGlobals::Instance();

$defines->addIncludeLocation("beans/");
$defines->addIncludeLocation("auth/");
$defines->addIncludeLocation("class/beans/");
$defines->addIncludeLocation("class/auth/");

include_once("utils/functions.php");

//$doc_root = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', realpath($_SERVER['SCRIPT_FILENAME']));
//$location = preg_replace("!^${doc_root}!", '', $install_path);
$doc_full = realpath($_SERVER['DOCUMENT_ROOT']);
$location = preg_replace("!^{$doc_full}!", "", $install_path);

//app/site deployment (server path)
$defines->set("INSTALL_PATH", $install_path);

//app/site deployment - HTTP accessible - without ending slash
$defines->set("LOCAL", $location);

$location = $defines->get("LOCAL");

//sparkbox frontend classes location (js/css/images) - HTTP accessible - without ending slash
$defines->set("SPARK_LOCAL", $location . "/sparkfront");

//administrative module location - HTTP accessible - without ending slash
$defines->set("ADMIN_LOCAL", $location . "/admin");
//data bean storage location - HTTP accessible - without ending slash
$defines->set("STORAGE_LOCAL", $location . "/storage.php");

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$defines->set("SITE_PROTOCOL", $protocol);

$site_domain = $_SERVER["HTTP_HOST"];
$defines->set("SITE_DOMAIN", $site_domain);

//URL of this site without path and ending slash '/'
$defines->set("SITE_URL", $protocol . $site_domain . $location);
$defines->set("TITLE_PATH_SEPARATOR", " :: ");
$defines->set("COOKIE_DOMAIN", ".".$site_domain); // or .domain.com
$defines->set("STORAGE_EXTERNAL", $protocol . $site_domain . $location . "/storage.php");

// error_reporting(E_ALL & ~E_WARNING);
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 0);

ini_set("session.use_strict_mode", 1);
ini_set("session.cookie_lifetime", 0);
ini_set("session.use_cookies", 1);
ini_set("session.use_only_cookies", 1);
//overwritten from session_start do not set cache_limiter
//try to enable only on specific pages
//ini_set("session.cache_limiter", "private");
//minutes - cache of the response
//ini_set("session.cache_expire", 60); //1 hour
//seconds
ini_set("session.gc_maxlifetime", 1440);
ini_set("zlib.output_compression", 1);

//disable automatic output buffering this is handled manually in SparkPage
//in php.ini
//ini_get("output_buffering", 0);

//important for storage cache to work is the timezone matching across the lamp stack, apache uses the system timezone
//mysql driver uses date.timezone to set the mysql timezone
$timezone = "Europe/Sofia";
if ($defines->get("TIMEZONE")) {
    $timezone = $defines->get("TIMEZONE");
}
ini_set("date.timezone", $timezone);

//merge get/post into the request array prevent cookie mix in
$_REQUEST = array_merge($_GET, $_POST);

$umf = ini_get("upload_max_filesize");
KMG($umf);
$pmf = ini_get("post_max_size");
KMG($pmf);
$ml = ini_get("memory_limit");
KMG($ml);

$defines->set("UPLOAD_MAX_SIZE", $pmf);
$defines->set("MEMORY_LIMIT", $ml);

//'base size' for all uploaded images
$defines->set("IMAGE_UPLOAD_DEFAULT_WIDTH", 1280);
$defines->set("IMAGE_UPLOAD_DEFAULT_HEIGHT", 720);

//IMAGE_UPLOAD_DOWNSCALE = true  | uploaded images with different size from 'base size' are downscaled to size (DEFAULT_WIDTH,DEFAULT_HEIGHT)
//IMAGE_UPLOAD_DOWNSCALE = false | uploaded images are not downscaled even if dimension differ from (DEFAULT_WIDTH,DEFAULT_HEIGHT)
$defines->set("IMAGE_UPLOAD_DOWNSCALE", TRUE);

//IMAGE_UPLOAD_UPSCALE = true  | uploaded images with different size from 'base size' are upscaled to size (DEFAULT_WIDTH,DEFAULT_HEIGHT)
//IMAGE_UPLOAD_UPSCALE = false | uploaded images are not upscaled even if dimension differ from (DEFAULT_WIDTH,DEFAULT_HEIGHT)
$defines->set("IMAGE_UPLOAD_UPSCALE", FALSE);
$defines->set("IMAGE_UPLOAD_STORE_QUALITY", 80);

//force output to webp
$defines->set("IMAGE_SCALER_OUTPUT_FORMAT", "image/webp");
$defines->set("IMAGE_SCALER_OUTPUT_QUALITY", 80);


//generic contact name and email
$defines->set("DEFAULT_EMAIL_NAME", $site_domain . " Administration");
$defines->set("DEFAULT_EMAIL_ADDRESS", "info@" . $site_domain);

//dafult name and email for the Mailer class
$defines->set("DEFAULT_SERVICE_NAME", $site_domain . " Administration");
$defines->set("DEFAULT_SERVICE_EMAIL", "info@" . $site_domain);

$defines->set("TRANSLATOR_ENABLED", FALSE);
$defines->set("DB_ENABLED", FALSE);

$defines->set("DEFAULT_LANGUAGE", "english");
$defines->set("DEFAULT_LANGUAGE_ISO3", "eng");
$defines->set("DEFAULT_LOCALE", "en-us");

$defines->set("DEFAULT_CURRENCY", "EUR");


$defines->set("STORAGE_CACHE_ENABLED", TRUE);
//time in seconds to expire the cached page components
$defines->set("PAGE_CACHE_TTL", 3600);
$defines->set("PAGE_CACHE_ENABLED", FALSE);
$defines->set("PAGE_CACHE_CLEANUP_DELTA", 86400);
//filesystem or database
$defines->set("BEAN_CACHE_BACKEND", "filesystem");
$defines->set("PAGE_CACHE_BACKEND", "filesystem");
//slugify StorageItem returned urls
$defines->set("STORAGE_ITEM_SLUGIFY_URLS", FALSE);


//fetch local deployment configuration
//can override stuff in defines
include_once("config/defaults.php");

$site_title = $defines->get("SITE_TITLE");
if (!$site_title) throw new Exception("SITE_TITLE not defined");

$defines->set("CACHE_PATH", dirname($install_path) . DIRECTORY_SEPARATOR . "sparkcache" . DIRECTORY_SEPARATOR . $defines->get("SITE_TITLE"));

ini_set('intl.default_locale', $defines->get("DEFAULT_LOCALE"));

$defines->export();

if (!file_exists(CACHE_PATH)) {
    debug("Creating cache folder: " . CACHE_PATH);
    @mkdir(CACHE_PATH, 0777, TRUE);
    if (!file_exists(CACHE_PATH)) throw new Exception("Unable to create cache folder: " . CACHE_PATH);
}

//
//define SKIP_DB to skip creating a default connection to DB
if (DB_ENABLED && !defined("SKIP_DB")) {

    include_once("dbdriver/DBDriver.php");
    include_once("dbdriver/DBConnections.php");
    include_once("objects/SparkEventManager.php");
    include_once("objects/SparkObserver.php");

    //fetch local config
    include_once("config/dbconfig.php");
    SparkEventManager::register(DBDriverEvent::class, new SparkObserver(DBConnections::connectionEvent(...)));
}

include_once("utils/language.php");

// $constants = get_defined_constants(true);
// debug("Exported Globals: ",$constants["user"]);
//global site wide function
@include_once("config/globals.php");

include_once("utils/ErrorHandler.php");
function exception_error_handler(int $errNo, string $errStr, string $errFile, int $errLine) {
    throw new ErrorHandler($errNo, $errStr, $errFile, $errLine);
}
//make all errors an exception even the warnings
//set_error_handler("exception_error_handler", E_ALL);

?>
