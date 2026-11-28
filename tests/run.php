<?php
/**
 * SparkBox test runner
 *
 *   php tests/run.php                  all core tests
 *   php tests/run.php storage          only test files with 'storage' in the name
 *   php tests/run.php --dir=/path      run *.test.php files of another folder (site tests)
 *   php tests/run.php --jobs=4         run 4 test files at a time (default 1, or SPARK_TEST_JOBS); the results print
 *                                      in file order. A file with '@serial' in its header comment shares state with
 *                                      the other such files (the test client of a site) - they run one at a time.
 *
 * Every run recreates the test tables (tests/schema.sql) and empties the test cache folder.
 * Each test file runs in its own PHP process. Exit code 0 = all passed.
 */
include_once(__DIR__ . "/harness/bootstrap_light.php");

$filter = "";
$jobs = max(1, (int)(getenv("SPARK_TEST_JOBS") ?: 1));
$dirs = array(__DIR__ . "/core");
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, "--dir=")) {
        $dirs = array(substr($arg, 6));
    }
    else if (str_starts_with($arg, "--jobs=")) {
        $jobs = max(1, (int)substr($arg, 7));
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

//the files run in their own processes, up to $jobs at a time; each prints when done, in file order
$command = function (string $file) : string {
    return escapeshellarg(PHP_BINARY) . " " . escapeshellarg(__DIR__ . "/harness/runfile.php") . " " . escapeshellarg($file) . " 2>&1";
};
$running = array();
$results = array();
$printed = 0;
$report = function (string $file, string $text, int $code, float $seconds) use (&$totals) : void {
    printf("\n%s (%.1fs)\n", basename($file), $seconds);
    $unexpected = array();
    foreach (explode("\n", $text) as $line) {
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
};
//the files marked '@serial' (in their first lines)
$serial = array();
foreach ($files as $index => $file) {
    $serial[$index] = str_contains(implode("", array_slice(file($file), 0, 15)), "@serial");
}
$started_ = array();
while ($printed < count($files)) {
    //start the next files: a serial file only when no other serial file runs
    $serialRunning = count(array_filter(array_keys($running), fn(int $i) => $serial[$i])) > 0;
    foreach (array_keys($files) as $index) {
        if (count($running) >= $jobs) break;
        if (isset($started_[$index])) continue;
        if ($serial[$index] && $serialRunning) continue;
        $process = proc_open($command($files[$index]), array(1 => array("pipe", "w")), $pipes);
        stream_set_blocking($pipes[1], false);
        $running[$index] = array($process, $pipes[1], "", microtime(true));
        $started_[$index] = true;
        if ($serial[$index]) $serialRunning = true;
    }
    foreach ($running as $index => [$process, $pipe, $text, $fileStarted]) {
        $running[$index][2] .= (string)stream_get_contents($pipe);
        $status = proc_get_status($process);
        if ($status["running"]) continue;
        $running[$index][2] .= (string)stream_get_contents($pipe);
        fclose($pipe);
        proc_close($process);
        $results[$index] = array($running[$index][2], (int)$status["exitcode"], microtime(true) - $fileStarted);
        unset($running[$index]);
    }
    while (isset($results[$printed])) {
        [$text, $code, $seconds] = $results[$printed];
        $report($files[$printed], $text, $code, $seconds);
        unset($results[$printed]);
        $printed++;
    }
    if (count($running) > 0) usleep(20000);
}

printf("\n%d passed, %d failed, %d skipped in %.1fs\n", $totals["PASS"], $totals["FAIL"], $totals["SKIP"], microtime(true) - $started);
exit($totals["FAIL"] > 0 ? 1 : 0);
