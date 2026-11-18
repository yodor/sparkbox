<?php
/**
 * Strict includes of a site before a deploy: the classes its class/ files use without their include_once
 * (the module contents are no longer loaded by name - a path file includes its content class).
 *
 *   php tests/check_includes.php <site root> [<sparkstore lib>]
 *
 * The store lib defaults to ../sparkstore/lib next to this sparkbox, when the site uses it (config/include.php).
 * Prints "file: Class" lines; exit code 1 when something is missing.
 */
include_once(__DIR__ . "/harness/includes.php");

$site = realpath((string)($argv[1] ?? ""));
if (!$site || !is_dir("$site/class")) {
    fwrite(STDERR, "usage: php tests/check_includes.php <site root> [<sparkstore lib>]\n");
    exit(2);
}
$core = realpath(__DIR__ . "/../lib");
$roots = array($core);
$store = realpath((string)($argv[2] ?? "$core/../../sparkstore/lib"));
$usesStore = is_file("$site/config/include.php") && str_contains((string)file_get_contents("$site/config/include.php"), "sparkstore");
if ($store && $usesStore) $roots[] = $store;
$roots[] = $site;

$missing = includesMissing($roots, "$site/class");
foreach ($missing as $line) echo str_replace("$site/", "", $line), "\n";
echo count($missing) ? count($missing) . " missing\n" : "ok\n";
exit(count($missing) ? 1 : 0);
