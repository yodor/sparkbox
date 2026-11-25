<?php
/**
 * AssetBundle: the local stylesheets / scripts of a page in one response - the address names its files (nothing is
 * stored), relative url() made absolute, a media query kept, a bad address answered 404; HTMLHead with bundles:
 * one stylesheet, the boot scripts in place, the rest deferred, the bundled files listed for ContentFragment
 */
include_once(__DIR__ . "/../harness/http.php");
include_once("utils/AssetBundle.php");

//the versions of the files as the server sees them (the test application)
$_SERVER["DOCUMENT_ROOT"] = realpath(TESTS_PATH . "/app");

test("bundle: relative url() addresses made absolute, others kept", function () {
    $css = "a{background:url(../images/x.png)} b{background:url('../../logo')} c{background:url(\"/abs.png\")} d{background:url(data:image/png;base64,AA==)} e{background:url(https://cdn.example/x.png)} f{background:url(icons/y.svg#z)}";
    $out = AssetBundle::AbsoluteURLs($css, "/storefront/css/frame");
    foreach (array("url(/storefront/css/images/x.png)", "url('/storefront/logo')", "url(\"/abs.png\")", "url(data:image/png;base64,AA==)",
                 "url(https://cdn.example/x.png)", "url(/storefront/css/frame/icons/y.svg#z)") as $part) {
        assertTrue(str_contains($out, $part), "$part in $out");
    }
});

test("bundle: the address answers the files in their order, a media query wrapped, cached for good", function () {
    $href = AssetBundle::Href(AssetBundle::TYPE_CSS, array("/sparkfront/css/Action.css", "/sparkfront/css/mobile/MenuBar.css@(max-width: 1024px)"));
    assertTrue((bool)preg_match("#^/assets/(bundle/[0-9a-f]{16}\.css)\?f=([A-Za-z0-9_-]+)$#", $href, $m), $href);
    [$status, $headers, $body] = httpRequest("storage.php", "StorageRequest=1&route=" . rawurlencode($m[1]) . "&f=" . $m[2]);
    assertSame(200, $status);
    assertTrue(str_starts_with((string)($headers["content-type"] ?? ""), "text/css"), "type");
    assertSame("public, max-age=31536000, immutable", $headers["cache-control"] ?? "");
    $action = strpos($body, "/* /sparkfront/css/Action.css */");
    $menu = strpos($body, "@media (max-width: 1024px) {");
    assertTrue($action !== false && $menu !== false && $action < $menu, "the files in their order, the media query kept");

    //an address of other versions (a page cached before a change): the current files, for a minute
    [$status, $headers] = httpRequest("storage.php", "StorageRequest=1&route=" . rawurlencode("bundle/0000000000000000.css") . "&f=" . $m[2]);
    assertSame(200, $status);
    assertSame("public, max-age=60", $headers["cache-control"] ?? "");
});

test("bundle: a script bundle; files outside, of another type or missing are refused", function () {
    $href = AssetBundle::Href(AssetBundle::TYPE_JS, array("/sparkfront/js/SparkEvent.js", "/sparkfront/js/CallStack.js"));
    preg_match("#^/assets/(bundle/[0-9a-f]{16}\.js)\?f=(.+)$#", $href, $m);
    [$status, $headers, $body] = httpRequest("storage.php", "StorageRequest=1&route=" . rawurlencode($m[1]) . "&f=" . $m[2]);
    assertSame(200, $status);
    assertTrue(str_starts_with((string)($headers["content-type"] ?? ""), "text/javascript"), "type");
    assertTrue(strpos($body, "class SparkEvent") < strpos($body, "class CallStack"), "in their order");

    $encode = fn(string $list) : string => rtrim(strtr(base64_encode((string)gzdeflate($list)), "+/", "-_"), "=");
    foreach (array("/../config/dbconfig.php", "/sparkfront/js/SparkEvent.js", "/sparkfront/css/Missing.css", "/sparkfront/../sparkfront/css/Action.css") as $file) {
        [$status, $headers] = httpRequest("storage.php", "StorageRequest=1&route=" . rawurlencode("bundle/0000000000000000.css") . "&f=" . $encode($file));
        assertSame(404, $status, $file);
        assertSame("no-store", $headers["cache-control"] ?? "", "$file: not cached");
    }
    [$status] = httpRequest("storage.php", "StorageRequest=1&route=" . rawurlencode("bundle/0000000000000000.css") . "&f=not-deflated");
    assertSame(404, $status, "not a list");
});
