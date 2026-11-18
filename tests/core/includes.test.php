<?php
/**
 * Strict includes of the core lib and the store lib next to it (../sparkstore/lib) - harness/includes.php
 */
include_once(__DIR__ . "/../harness/includes.php");

test("strict includes: every class a file uses is reachable through its own include_once chain", function () {
    $core = realpath(__DIR__ . "/../../lib");
    $roots = array($core);
    $store = realpath($core . "/../../sparkstore/lib");
    if ($store) $roots[] = $store;
    $missing = array();
    foreach ($roots as $root) $missing = array_merge($missing, includesMissing($roots, $root));
    assertSame(array(), $missing, "classes used without their include_once");
});
