<?php
/**
 * The front controller (the root index.php, Module::Route): the modules of config/routes.php by their address
 * folder - the request as the server sends it without module folders (index.php?path=/<address>, QSA). The
 * addresses with values (addPattern) answer as the module .htaccess rules did.
 */
include_once(__DIR__ . "/../harness/http.php");

/**
 * '$address' as the root .htaccess passes it to index.php
 */
function rootRequest(string $address, array $options = array()) : array
{
    $parts = explode("?", $address, 2);
    $query = "path=" . rawurlencode($parts[0]) . (isset($parts[1]) ? "&" . $parts[1] : "");
    return httpRequest("index.php", $query, array(), $address, $options);
}

test("front controller: a news item, the archive and the list at their addresses (patterns of the news module)", function () {
    $pdo = TestPDO();
    httpRequest("news/index.php", "", array(), "/news/");
    $tag = "fc" . bin2hex(random_bytes(3));
    $pdo->prepare("INSERT INTO news_items (item_title, item_date, content, photo) VALUES (?, '2026-03-05', '<p>Front text</p>', '')")
        ->execute(array("Front One $tag"));
    $id = (int)$pdo->lastInsertId();
    try {
        [$status, , $body] = rootRequest("/news/$id/front-one-$tag/");
        assertSame(200, $status, "item");
        assertTrue(str_contains($body, "<title>Front One $tag"), "the item (newsID from the address)");
        assertTrue((bool)preg_match("#<body[^>]*class='[^']*\\bnews view\\b#", $body), "module news, path view");

        [$status, , $body] = rootRequest("/news/archive/2026/3/");
        assertSame(200, $status, "archive");
        assertTrue(str_contains($body, "Front One $tag"), "archive month (year, month from the address)");

        [$status, , $body] = rootRequest("/news/");
        assertSame(200, $status, "list");
        assertTrue(str_contains($body, "href='/news/$id/front-one-$tag/'"), "item link");

        [$status, $headers] = rootRequest("/news/$id/old-title/");
        assertSame(301, $status, "wrong slug");
        assertTrue(str_ends_with($headers["location"] ?? "", "/news/$id/front-one-$tag/"), "canonical address");
    }
    finally {
        $pdo->exec("DELETE FROM news_items WHERE item_title LIKE '%$tag'");
    }
});

test("front controller: the module folder gets its slash, the script address and its own ?path= work", function () {
    [$status, $headers] = rootRequest("/admin?x=1");
    assertSame(301, $status);
    assertSame("/admin/?x=1", $headers["location"] ?? "");

    [$status, $headers] = rootRequest("/admin/");
    assertSame(302, $status, "admin home without login");
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/login"), "the login of the admin module: " . ($headers["location"] ?? ""));

    [$status, , $body] = rootRequest("/admin/index.php?path=/login");
    assertSame(200, $status, "the module script with its own path");
    assertTrue((bool)preg_match("#<body[^>]*class='[^']*\\badmin login\\b#", $body), "admin login page");

    [$status, , $body] = rootRequest("/manage/login");
    assertSame(200, $status, "the admin module at another folder (AddRoute('manage', 'admin'))");
    assertTrue(str_contains($body, "action='/manage/") || str_contains($body, "'/manage/"), "its addresses at its folder");
});

test("front controller: a folder that is not a route is a path of the main module", function () {
    [$status, , $body] = rootRequest("/hello");
    assertSame(200, $status);
    assertSame("hello script main", $body);

    [$status, , $body] = rootRequest("/nosuchmodule/page");
    assertSame(404, $status, "main module 404");
    assertTrue((bool)preg_match("#<body[^>]*class='[^']*\\bmain\\b#", $body), "the main module answers");
});
