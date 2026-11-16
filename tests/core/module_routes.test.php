<?php
/**
 * Module routing: a page class per path (addPagePath) and old addresses (addRedirect) - tests/app/routetest
 */
include_once(__DIR__ . "/../harness/http.php");

function routeRequest(string $query, string $uri) : array
{
    return httpRequest("routetest/index.php", $query, array(), $uri);
}

test("module routes: the module page for its paths, the path page class for its path and below", function () {
    [$status, , $body] = routeRequest("", "/routetest/");
    assertSame(200, $status, "home");
    assertTrue(str_contains($body, "<title>Route Home") && !str_contains($body, "route_test"), "module page");

    foreach (array("path=%2Fitem&itemID=5" => "/routetest/item?itemID=5", "path=%2Fitem%2Fphotos&itemID=5" => "/routetest/item/photos?itemID=5") as $query => $uri) {
        [$status, , $body] = routeRequest($query, $uri);
        assertSame(200, $status, $uri);
        assertTrue(str_contains($body, "<div class='route_test'>initialize;processInput:5;</div>"), "$uri: page class of the path, its input processed");
    }
});

test("module routes: old addresses answer 301 to their path with the query", function () {
    [$status, $headers] = routeRequest("path=%2Flist.php&q=abc&page=2", "/routetest/list.php?q=abc&page=2");
    assertSame(301, $status, "list.php");
    assertSame("/routetest/?q=abc&page=2", $headers["location"] ?? "", "to the module folder");
    [$status, $headers] = routeRequest("path=%2Fitem.php&itemID=7", "/routetest/item.php?itemID=7");
    assertSame(301, $status, "item.php");
    assertSame("/routetest/item?itemID=7", $headers["location"] ?? "", "to the item path");
});
