<?php

// $base_dir  = __DIR__; // Absolute path to your installation, ex: /var/www/mywebsite
// $doc_root  = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
// $base_url  = preg_replace("!^${doc_root}!", '', $base_dir); # ex: '' or '/mywebsite'
// $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
// $port      = $_SERVER['SERVER_PORT'];
// $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
// $domain    = $_SERVER['SERVER_NAME'];
// $full_url  = "${protocol}://${domain}${disp_port}${base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc.

//define these to any value

//no forced redirect to https if it is in http
//$GLOBALS["FORCE_HTTPS"] = "";
//no subdomain redirect
//$GLOBALS["NO_SUBDOMAIN_REDIRECT"] = "";
//default redirect subdomains or set to the desired subdomains
//$GLOBALS["REDIRECT_SUBDOMAINS"] = "www;mail";
if (!isset($install_path)) throw new Exception("Install path is not defined");

if (defined("REQUEST_THROTTLE_USERAGENT")) {
    include_once("ratelimit.php");
}

//auto https redirect
include("redirect.php");

//config constants
include_once("config.php");

//Spark config and utility functions
include_once("spark.php");

//debugging helper
include_once("debug.php");


Spark::Initialize($install_path);

Spark::EnableBeanLocation("beans/");
Spark::EnableBeanLocation("auth/");
Spark::EnableBeanLocation("class/beans/");
Spark::EnableBeanLocation("class/auth/");


//call local deployment configuration
include_once("config/defaults.php");

//merge get/post into the request array prevent cookie mix in
$_REQUEST = array_merge($_GET, $_POST);

// error_reporting(E_ALL & ~E_WARNING);
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 0);

ini_set("session.use_strict_mode", 1);
ini_set("session.cookie_lifetime", 0);
ini_set("session.use_cookies", 1);
ini_set("session.use_only_cookies", 1);
ini_set("session.cookie_secure", 1);
ini_set("session.cookie_httponly", 1);
//	"Lax" or "Strict"
ini_set("session.cookie_samesite", "Strict");
// Optional: longer session ID


//overwritten from session_start do not set cache_limiter
//try to enable only on specific pages
//ini_set("session.cache_limiter", "private");
//minutes - cache of the response
//ini_set("session.cache_expire", 60); //1 hour
//seconds
ini_set("session.gc_maxlifetime", 1440);

ini_set("zlib.output_compression", 1);

ini_set('intl.default_locale', Spark::Get(Config::DEFAULT_LOCALE));

//disable automatic output buffering this is handled manually in SparkPage
//in php.ini
//ini_get("output_buffering", 0);

//important for storage cache to work is the timezone matching across the lamp stack, apache uses the system timezone
//mysql driver uses date.timezone to set the mysql timezone
ini_set("date.timezone", Spark::Get(Config::TIMEZONE));

//set path and create folder if not exists
Spark::Set(Config::CACHE_PATH, Spark::CachePath(), true);


//Spark::DefineConfig();

//
//define SKIP_DB to skip creating a default connection to DB
if (Spark::Get(Config::DB_ENABLED) && !defined("SKIP_DB")) {

    include_once("dbdriver/DBDriver.php");
    include_once("dbdriver/DBConnections.php");
    include_once("objects/SparkEventManager.php");
    include_once("objects/SparkObserver.php");

    //fetch local config
    include_once("config/dbconfig.php");
    SparkEventManager::register(DBDriverEvent::class, new SparkObserver(DBConnections::connectionEvent(...)));
}

include_once("utils/language.php");


//global site wide function
@include_once("config/globals.php");

include_once("utils/ErrorHandler.php");
function exception_error_handler(int $errNo, string $errStr, string $errFile, int $errLine) {
    throw new ErrorHandler($errNo, $errStr, $errFile, $errLine);
}
//make all errors an exception even the warnings
//set_error_handler("exception_error_handler", E_ALL);

?>