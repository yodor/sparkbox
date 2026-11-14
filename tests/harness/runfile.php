<?php
/**
 * Runs the tests of one file in its own process - static state, the DB pool and output buffers do not
 * leak between files. Called by run.php: php harness/runfile.php <test file>
 */
include_once(__DIR__ . "/assert.php");
include_once(__DIR__ . "/bootstrap.php");

$file = $argv[1] ?? "";
if (!is_file($file)) {
    echo "FAIL " . basename($file) . " | test file not found\n";
    exit(1);
}

include($file);

foreach (Tests::$registered as $name => $fn) {
    ob_start();
    try {
        $fn();
        ob_end_clean();
        echo "PASS $name\n";
    }
    catch (TestSkipped $e) {
        ob_end_clean();
        echo "SKIP $name | " . $e->getMessage() . "\n";
    }
    catch (Throwable $e) {
        ob_end_clean();
        $where = ($e instanceof TestFailed) ? "" : " (" . get_class($e) . " at " . basename($e->getFile()) . ":" . $e->getLine() . ")";
        echo "FAIL $name | " . str_replace("\n", " ", $e->getMessage()) . $where . "\n";
    }
}
