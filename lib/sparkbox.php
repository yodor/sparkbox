<?php


if (!defined("APP_PATH")) throw new Exception("APP_PATH is not defined");

if (file_exists(APP_PATH."/config/boot.php")) include_once(APP_PATH."/config/boot.php");

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
if (defined("DEBUG_LEVEL")) {
    Debug::$traceDepth = intval(DEBUG_LEVEL);
}
if (getenv("DEBUG_LEVEL", true)!==false) {
    Debug::$traceDepth = intval(getenv("DEBUG_LEVEL", true));
}

Spark::Set(Config::SPARKBOX_PATH, realpath(dirname(__DIR__)), true);

//append sparkbox/lib to include_path and loader locations
Spark::IncludePath(Spark::PathParts(Spark::GetString(Config::SPARKBOX_PATH),"lib"), "");


Spark::Initialize();

//call local deployment configuration
if (file_exists(APP_PATH."/config/settings.php")) include_once(APP_PATH."/config/settings.php");

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

//creating a default connection to DB
if (Spark::GetBoolean(Config::DB_ENABLED)) {
    include_once("dbdriver/DBDriver.php");
    include_once("dbdriver/DBConnections.php");

    //fetch local config
    require_once(APP_PATH."/config/dbconfig.php");

    //include_once("objects/SparkObserver.php");
    //SparkEventManager::register(DBDriverEvent::class, new SparkObserver(DBConnections::connectionEvent(...)));
}

if (!Spark::isStorageRequest()) {

    include_once("utils/language.php");

    if (!Spark::isJSONRequest()) {
        include_once("objects/SparkEventManager.php");
        include_once("pages/HTMLHead.php");
        include_once("pages/HTMLBody.php");
        SparkEventManager::register(ComponentEvent::class, HTMLHead::Instance());
        SparkEventManager::register(ComponentEvent::class, HTMLBody::Instance());
    }

    register_shutdown_function(function(){
        include_once("storage/CacheFactory.php");
        CacheFactory::CleanupPageCache();
    });

    include_once("utils/ErrorHandler.php");
    function exception_error_handler(int $errNo, string $errStr, string $errFile, int $errLine) {
        throw new ErrorHandler($errNo, $errStr, $errFile, $errLine);
    }
    //make all errors an exception even the warnings
    //set_error_handler("exception_error_handler", E_ALL);
}


//other libs initialization
if (file_exists(APP_PATH."/config/include.php")) include_once(APP_PATH."/config/include.php");
//set app include path
Spark::IncludePath(Spark::PathParts(APP_PATH), "class");
//boot complete local app initialization
if (file_exists(APP_PATH."/config/app.php")) include_once(APP_PATH."/config/app.php");

if (Spark::isStorageRequest()) {
    include_once("sparkstorage.php");
    exit;
}