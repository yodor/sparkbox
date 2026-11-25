<?php
/**
 * The pages module (the route pages of tests/app/config/routes.php): dynamic pages, their group menu, the module addresses.
 * Adds pages to the test database and removes them.
 */
include_once(__DIR__ . "/../harness/http.php");

/**
 * A request the way the module rewrite passes it: /pages/<dpID>/<slug>/ → index.php?path=/view&dpID=
 */
function pagesRequest(string $query, string $uri) : array
{
    return httpRequest("pages/index.php", $query, array(), $uri);
}

/**
 * Test pages of a group of its own - returns [tag, ids by title]
 */
function pagesSeed(PDO $pdo) : array
{
    //the bean creates the table on a fresh database
    pagesRequest("", "/pages/");
    $tag = "pg" . bin2hex(random_bytes(3));
    $rows = array(
        array("First Page", "test_footer;$tag;first;", 2, "2026-01-01"),
        array("Second Page", "test_footer;$tag;second;", 1, "2026-02-01"),
        array("Other Group", "test_footer;{$tag}x;other;", 3, "2026-03-01"),
        array("Hidden Page", "test_footer;$tag;hidden;", 4, "2026-04-01"),
    );
    $insert = $pdo->prepare("INSERT INTO dynamic_pages (item_title, item_date, visible, content, keywords, position) VALUES (?, ?, ?, ?, ?, ?)");
    $ids = array();
    foreach ($rows as [$title, $keywords, $position, $date]) {
        $insert->execute(array($title, $date, ($title === "Hidden Page") ? 0 : 1, "<p>Content of $title</p>", $keywords, $position));
        $ids[$title] = (int)$pdo->lastInsertId();
    }
    return array($tag, $ids);
}

test("pages: a page at its address - title, content, the group menu (whole levels, admin position), canonical", function () {
    $pdo = TestPDO();
    [$tag, $ids] = pagesSeed($pdo);
    try {
        $id = $ids["First Page"];
        [$status, , $body] = pagesRequest("path=%2Fview&dpID=$id", "/pages/$id/first-page/");
        assertSame(200, $status, "page");
        assertTrue(str_contains($body, "<title>First Page"), "title");
        assertTrue(str_contains($body, "<p>Content of First Page</p>"), "content");
        assertTrue((bool)preg_match("#<link rel='canonical' href='[^']*//localhost/pages/$id/first-page/'>#", $body), "canonical address");
        //one language: no hreflang alternates (they come with the language in the address)
        assertTrue(!str_contains($body, "hreflang") && !str_contains($body, "change_language"), "language links");
        assertTrue(str_contains($body, "page_menu test_footer $tag first"), "menu with the page levels");
        //the group of the page: test_footer;<tag> - the Other Group (<tag>x) is not a whole level match, hidden is not listed
        preg_match_all("#<a class='item[^']*' href='/pages/(\\d+)/#", $body, $m);
        assertSame(array($ids["Second Page"], $id), array_map("intval", $m[1]), "menu items in admin position order");
        assertTrue(str_contains($body, "class='item selected'"), "current page marked");
    }
    finally {
        $pdo->exec("DELETE FROM dynamic_pages WHERE keywords LIKE '%$tag%'");
    }
});

test("pages: old and other addresses - 301 to the page address, a group to its newest page, unknown to home", function () {
    $pdo = TestPDO();
    [$tag, $ids] = pagesSeed($pdo);
    try {
        $id = $ids["First Page"];
        foreach (array("id=$id" => "/pages/index.php?id=$id", "page_id=$id" => "/pages/?page_id=$id") as $query => $uri) {
            [$status, $headers] = pagesRequest($query, $uri);
            assertSame(301, $status, $uri);
            assertTrue(str_ends_with($headers["location"] ?? "", "/pages/$id/first-page/"), "$uri: " . ($headers["location"] ?? ""));
        }
        //a wrong slug
        [$status, $headers] = pagesRequest("path=%2Fview&dpID=$id", "/pages/$id/old-title/");
        assertSame(301, $status, "old slug");
        assertTrue(str_ends_with($headers["location"] ?? "", "/pages/$id/first-page/"), "old slug: " . ($headers["location"] ?? ""));

        //a group: its newest visible page
        [$status, $headers] = pagesRequest(http_build_query(array("page_class" => "test_footer;$tag")), "/pages/?page_class=test_footer;$tag");
        assertSame(302, $status, "group");
        assertTrue(str_ends_with($headers["location"] ?? "", "/pages/" . $ids["Second Page"] . "/second-page/"), "group: " . ($headers["location"] ?? ""));
        [$status, $headers] = pagesRequest(http_build_query(array("class" => "$tag;first")), "/pages/?class=$tag;first");
        assertTrue(str_ends_with($headers["location"] ?? "", "/pages/$id/first-page/"), "class: " . ($headers["location"] ?? ""));

        //hidden, unknown, a group with markup
        foreach (array("path=%2Fview&dpID=" . $ids["Hidden Page"], "path=%2Fview&dpID=999999", http_build_query(array("page_class" => "x' onmouseover='1"))) as $query) {
            [$status, $headers] = pagesRequest($query, "/pages/?$query");
            assertSame(302, $status, $query);
            assertSame("/", $headers["location"] ?? "", "$query: to home");
        }
    }
    finally {
        $pdo->exec("DELETE FROM dynamic_pages WHERE keywords LIKE '%$tag%'");
    }
});
