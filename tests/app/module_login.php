<?php
/**
 * Test only - the login redirect of a module with the loginAlert given as ?alert= (empty: none).
 * Runs only through php-cgi started by the harness (SPARK_TESTS=1).
 */
if (!str_starts_with(PHP_SAPI, "cgi") || getenv("SPARK_TESTS") !== "1") {
    exit;
}
include_once("templates/Template.php");
include_once("templates/Module.php");

$module = Module::Factory("admin", "admin/");
$module->initialize();
$module->loginAlert = (string)($_GET["alert"] ?? "");
$module->loginRedirect("the test");
