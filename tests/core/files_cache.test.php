<?php
/**
 * SparkFile and FileCacheEntry: atomic replace under concurrent reads, path rules, missing files.
 */
include_once("storage/SparkFile.php");
include_once("storage/FileCacheEntry.php");

test("cache entry stores data and mtime, isCurrent() compares the mtime", function () {
    $entry = new FileCacheEntry(Spark::Get(Config::CACHE_PATH), "entry-a");
    $entry->store("hello", 1600000000);
    assertTrue($entry->isCurrent(1600000000));
    assertTrue(!$entry->isCurrent(1600000001));
    assertSame(0, count(glob(Spark::Get(Config::CACHE_PATH) . "/*.tmp")), "temporary files left");
});

test("readers never see a partial file while it is replaced", function () {
    $folder = Spark::Get(Config::CACHE_PATH);
    $entry = new FileCacheEntry($folder, "entry-b");
    $entry->store(str_repeat("A", 150000));
    $writer = PHP_BINARY . " -r " . escapeshellarg(
        'set_include_path(' . var_export(get_include_path(), true) . '); define("APP_PATH","/tmp");'
        . 'include_once("config.php"); include_once("spark.php"); include_once("debug.php"); ini_set("error_log","/dev/null");'
        . 'include_once("storage/FileCacheEntry.php"); $e = new FileCacheEntry(' . var_export($folder, true) . ', "entry-b");'
        . 'for ($i = 0; $i < 1500; $i++) { $e->store(str_repeat(chr(65 + $i % 26), ($i % 2) ? 200000 : 150000)); }');
    $process = proc_open($writer, array(), $pipes);
    $broken = 0;
    $reads = 0;
    while (proc_get_status($process)["running"]) {
        ob_start();
        $entry->output();
        $data = ob_get_clean();
        $reads++;
        if (!in_array(strlen($data), array(150000, 200000)) || $data !== str_repeat($data[0], strlen($data))) $broken++;
    }
    proc_close($process);
    assertTrue($reads > 100, "too few reads ($reads)");
    assertSame(0, $broken, "broken reads out of $reads");
});

test("relative and empty paths are rejected", function () {
    assertThrows(fn() => new SparkFile("relative/name"), "absolute");
    assertThrows(function () { $f = new SparkFile(); $f->setPath(""); }, "Empty path");
});

test("missing file: lastModified throws, remove returns false, MIME falls back", function () {
    $file = new SparkFile(Spark::Get(Config::CACHE_PATH) . "/does-not-exist");
    assertThrows(fn() => $file->lastModified());
    assertSame(false, $file->remove());
    assertSame("application/octet-stream", $file->getMIME());
});

test("setFilename keeps only the name part", function () {
    $file = new SparkFile();
    $file->setPath("/tmp");
    $file->setFilename("../../etc/passwd");
    assertSame("/tmp/passwd", $file->getAbsoluteFilename());
});
