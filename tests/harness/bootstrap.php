<?php
/**
 * In-process bootstrap for the core tests: the library on the include path, APP_PATH = tests/app,
 * a cache folder per run and the test database registered in DBManager.
 * HTTP level tests go through tests/app/*.php and the real sparkbox.php bootstrap instead (see http.php).
 */
include_once(__DIR__ . "/bootstrap_light.php");

error_reporting(E_ALL);
ini_set("display_errors", "1");

set_include_path(realpath(TESTS_PATH . "/../lib") . PATH_SEPARATOR . TESTS_PATH . "/app" . PATH_SEPARATOR . get_include_path());
define("APP_PATH", realpath(TESTS_PATH . "/app"));

include_once("config.php");
include_once("spark.php");
include_once("debug.php");
//library debug output goes to the log, not into the test output
ini_set("error_log", TestCachePath() . "/debug.log");

@mkdir(TestCachePath(), 0777, true);
Spark::Set(Config::CACHE_PATH, TestCachePath());

include_once("dbdriver/DBManager.php");
include_once("dbdriver/PDODriver.php");

$c = TestConfig();
$dbconfig = new DBConfig();
$dbconfig->host = $c["db_host"];
$dbconfig->port = $c["db_port"];
$dbconfig->database = $c["db_name"];
$dbconfig->user = $c["db_user"];
$dbconfig->pass = $c["db_pass"];
DBManager::Add($dbconfig);
