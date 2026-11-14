<?php
/**
 * SparkBox test runner
 *
 *   php tests/run.php                  all core tests
 *   php tests/run.php storage          only test files with 'storage' in the name
 *   php tests/run.php --dir=/path      run *.test.php files of another folder (site tests)
 *
 * Every run recreates the test tables (tests/schema.sql) and empties the test cache folder.
 * Each test file runs in its own PHP process. Exit code 0 = all passed.
 */
include_once(__DIR__ . "/harness/bootstrap_light.php");

$filter = "";
$dirs = array(__DIR__ . "/core");
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, "--dir=")) {
        $dirs = array(substr($arg, 6));
    }
    else {
        $filter = $arg;
    }
}

//fresh database tables
$pdo = TestPDO();
foreach (array_filter(array_map("trim", explode(";", file_get_contents(__DIR__ . "/schema.sql")))) as $statement) {
    if (preg_match('/^(--[^\n]*\n?)*$/', $statement)) continue;
    $pdo->exec($statement);
}
$insert = $pdo->prepare("INSERT INTO items (catID, name, price, body) VALUES (?, ?, ?, ?)");
for ($i = 1; $i <= 200; $i++) {
    $insert->execute(array(1 + ($i % 2), "item$i", ($i * 37) % 100, str_repeat("x", 500)));
}

//fresh cache folders
foreach (array(TestCachePath(), __DIR__ . "/sparkcache") as $folder) {
    if (is_dir($folder)) exec("rm -rf " . escapeshellarg($folder));
    mkdir($folder, 0777, true);
}

$files = array();
foreach ($dirs as $dir) {
    foreach (glob(rtrim($dir, "/") . "/*.test.php") as $file) {
        if ($filter !== "" && !str_contains(basename($file), $filter)) continue;
        $files[] = $file;
    }
}
sort($files);

$totals = array("PASS" => 0, "FAIL" => 0, "SKIP" => 0);
$started = microtime(true);

foreach ($files as $file) {
    echo "\n" . basename($file) . "\n";
    $output = array();
    exec(escapeshellarg(PHP_BINARY) . " " . escapeshellarg(__DIR__ . "/harness/runfile.php") . " " . escapeshellarg($file) . " 2>&1", $output, $code);
    $unexpected = array();
    foreach ($output as $line) {
        $status = substr($line, 0, 4);
        if (isset($totals[$status])) {
            $totals[$status]++;
            echo "  " . $line . "\n";
        }
        else if (trim($line) !== "") {
            //anything else is unexpected output - a warning, a deprecation, a fatal error
            $unexpected[] = trim($line);
        }
    }
    if (count($unexpected) > 0 || $code !== 0) {
        $totals["FAIL"]++;
        echo "  FAIL " . basename($file) . " | exit code $code" . (count($unexpected) ? ", output:" : "") . "\n";
        foreach ($unexpected as $line) echo "       " . $line . "\n";
    }
}

printf("\n%d passed, %d failed, %d skipped in %.1fs\n", $totals["PASS"], $totals["FAIL"], $totals["SKIP"], microtime(true) - $started);
exit($totals["FAIL"] > 0 ? 1 : 0);
