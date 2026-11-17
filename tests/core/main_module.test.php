<?php
/**
 * The main module at the root (tests/app/index.php): the home, script paths, redirects and 404 for unknown paths
 */
include_once(__DIR__ . "/../harness/http.php");

function mainRequest(string $query, string $uri) : array
{
    return httpRequest("index.php", $query, array(), $uri);
}

test("main: the home of the module (core default), a script path, a file name path", function () {
    [$status, , $body] = mainRequest("", "/");
    assertSame(200, $status, "home");
    assertTrue(str_contains($body, "<html") && !str_contains($body, "Page not found"), "home page");
    assertTrue((bool)preg_match("#<body[^>]*class='[^']*\\bmain home\\b#", $body), "body class: the module and its path");

    [$status, $headers, $body] = mainRequest("path=%2Fhello", "/hello");
    assertSame(200, $status, "script path");
    assertSame("hello script main", $body);
    assertTrue(str_starts_with($headers["content-type"] ?? "", "text/plain"), "the script headers");

    [$status, , $body] = mainRequest("path=%2Ffeed.xml", "/feed.xml");
    assertSame("hello script main", $body, "a path mapped to a script (addScriptPath)");

    [$status, , $body] = mainRequest("path=%2Frobots.txt", "/robots.txt");
    assertSame("dotted script main", $body, "scripts/robots.txt.php - the path keeps its dot");

    foreach (array("/..%2Fhello", "/hel%20lo", "/.hello") as $path) {
        [$status] = mainRequest("path=" . urlencode(urldecode($path)), $path);
        assertSame(404, $status, "not a script name: $path");
    }
});

test("main: single pages have no trailing slash - 301 with the query; the root keeps its slash", function () {
    [$status, $headers] = mainRequest("path=%2Fhello%2F&x=1", "/hello/?x=1");
    assertSame(301, $status, "trailing slash");
    assertSame("/hello?x=1", $headers["location"] ?? "");
    [$status] = mainRequest("", "/");
    assertSame(200, $status, "root");
    [$status] = mainRequest("path=%2Fno%2Fsuch%2F", "/no/such/");
    assertSame(301, $status, "before the 404");
});

test("main: an old address answers 301 to its path with the query", function () {
    [$status, $headers] = mainRequest("path=%2Fold.php&x=1", "/old.php?x=1");
    assertSame(301, $status);
    assertTrue(str_ends_with($headers["location"] ?? "", "/hello?x=1"), $headers["location"] ?? "");
});

test("an unknown path answers 404 with a plain page - main and the other modules", function () {
    foreach (array(array("index.php", "/no/such/page"), array("pages/index.php", "/pages/no-such-page")) as [$script, $uri]) {
        $path = substr($uri, ($script === "index.php") ? 0 : strlen("/pages"));
        [$status, , $body] = httpRequest($script, "path=" . urlencode($path), array(), $uri);
        assertSame(404, $status, $uri);
        assertTrue(str_contains($body, "Page not found"), "$uri: title");
        assertTrue(str_contains($body, "name='robots' content='noindex'"), "$uri: noindex");
        assertTrue(!str_contains($body, "TemplateConfig not initialized"), "$uri: no internal message");
    }
});
