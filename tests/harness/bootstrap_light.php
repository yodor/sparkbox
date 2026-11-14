<?php
/**
 * Runner side helpers only - configuration, test PDO and cache path. No library loaded.
 */
const TESTS_PATH = __DIR__ . "/..";

function TestConfig() : array
{
    static $config = null;
    if ($config === null) {
        $file = TESTS_PATH . "/config.php";
        if (!is_file($file)) {
            fwrite(STDERR, "tests/config.php missing - copy tests/config.php.dist\n");
            exit(2);
        }
        $config = include($file);
    }
    return $config;
}

function TestCachePath() : string
{
    return getenv("SPARK_TEST_CACHE") ?: sys_get_temp_dir() . "/sparkbox-tests-cache";
}

/**
 * Plain PDO to the test database - for setup and for checks that must not go through the library
 * @return PDO
 */
function TestPDO() : PDO
{
    $c = TestConfig();
    return new PDO("mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4",
        $c['db_user'], $c['db_pass'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
}
