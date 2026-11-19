<?php
/**
 * Content fragments (ContentFragmentResponder): a module without a frame (tests/app/fragmenttest) answers the
 * content of its path; a framed module (tests/app/routetest) answers the content alone when a JSON request asks
 */
include_once(__DIR__ . "/../harness/http.php");

const FRAGMENT_JSON = "JSONRequest=1&responder=ContentFragmentResponder&function=render";

test("fragments: a module without a frame answers the content with its stylesheets - no page, not indexed", function () {
    [$status, $headers, $body] = httpRequest("fragmenttest/index.php", "", array(), "/fragmenttest/");
    assertSame(200, $status);
    assertTrue(!str_contains($body, "<html") && !str_contains($body, "<body"), "no page");
    assertTrue(str_contains($body, "<div class='fragment_test'>") && str_contains($body, "Fragment Action"), "the content");
    assertTrue((bool)preg_match("#<link rel='stylesheet' href='[^']*/Action\.css[^']*'>#", $body), "the stylesheet of its component");
    assertTrue(str_contains($body, "/fragment.css"), "the module stylesheet");
    assertSame("noindex", $headers["x-robots-tag"] ?? "", "not indexed");
});

test("fragments: a module without a frame answers JSON - contents, styles, scripts, page", function () {
    [$status, , $body] = httpRequest("fragmenttest/index.php", FRAGMENT_JSON, array(), "/fragmenttest/?" . FRAGMENT_JSON);
    assertSame(200, $status);
    $json = json_decode($body, true);
    assertTrue(is_array($json), "JSON: " . substr($body, 0, 200));
    assertSame("OK", $json["status"] ?? "", (string)($json["message"] ?? ""));
    assertTrue(str_starts_with((string)$json["contents"], "<div class='fragment_test'>"), "contents: the content component");
    $styles = implode(" ", $json["styles"] ?? array());
    assertTrue(str_contains($styles, "/Action.css") && str_contains($styles, "/fragment.css"), "styles: $styles");
    assertTrue(is_array($json["scripts"] ?? null) && array_key_exists("page", $json), "scripts and page");
});

test("fragments: an unknown path of a module without a frame - 404, not indexed", function () {
    [$status, $headers] = httpRequest("fragmenttest/index.php", "path=%2Fnosuch", array(), "/fragmenttest/nosuch");
    assertSame(404, $status);
    assertSame("noindex", $headers["x-robots-tag"] ?? "", "not indexed");
});

test("fragments: a framed module answers the content alone to a JSON request - the HTML page is unchanged", function () {
    [$status, , $body] = httpRequest("routetest/index.php", FRAGMENT_JSON, array(), "/routetest/?" . FRAGMENT_JSON);
    assertSame(200, $status);
    $json = json_decode($body, true);
    assertSame("OK", $json["status"] ?? "", substr($body, 0, 200));
    assertTrue(!str_contains((string)$json["contents"], "<body") && !str_contains((string)$json["contents"], "<title"), "no frame in contents");
    assertTrue(str_contains(implode(" ", $json["styles"]), "/SparkPage.css"), "the head of the frame");

    [$status, , $body] = httpRequest("routetest/index.php", "", array(), "/routetest/");
    assertSame(200, $status);
    assertTrue(str_contains($body, "<title>Route Home") && str_contains($body, "<body"), "the page");
});
