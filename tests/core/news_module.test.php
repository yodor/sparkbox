<?php
/**
 * The news module (tests/app/news -> modules/news): publications of NewsItemsBean. Adds items and removes them.
 */
include_once(__DIR__ . "/../harness/http.php");

function newsRequest(string $query, string $uri) : array
{
    return httpRequest("news/index.php", $query, array(), $uri);
}

/**
 * Two items in March 2026, one in April 2026 - returns [tag, ids by title]
 */
function newsSeed(PDO $pdo) : array
{
    //the bean creates the table on a fresh database
    newsRequest("", "/news/");
    $tag = "nw" . bin2hex(random_bytes(3));
    $insert = $pdo->prepare("INSERT INTO news_items (item_title, item_date, content, photo) VALUES (?, ?, ?, '')");
    $ids = array();
    foreach (array(array("March One $tag", "2026-03-05"), array("March Two $tag", "2026-03-20"), array("April One $tag", "2026-04-02")) as [$title, $date]) {
        $insert->execute(array($title, $date, "<p>Text of $title with some words in it</p>"));
        $ids[$title] = (int)$pdo->lastInsertId();
    }
    return array($tag, $ids);
}

test("news: home lists the newest with links to the items and the archive months", function () {
    $pdo = TestPDO();
    [$tag, $ids] = newsSeed($pdo);
    try {
        [$status, , $body] = newsRequest("", "/news/");
        assertSame(200, $status);
        assertTrue(str_contains($body, "<title>News"), "title");
        $id = $ids["April One $tag"];
        assertTrue(str_contains($body, "href='/news/$id/april-one-$tag/'"), "item link (latest / title)");
        assertTrue(str_contains($body, "href='/news/archive/2026/3/'") && str_contains($body, "href='/news/archive/2026/4/'"), "archive month links");
        assertTrue(str_contains($body, "<aside class='column'>") && str_contains($body, "<main class='column'>"), "aside and main columns");
    }
    finally {
        $pdo->exec("DELETE FROM news_items WHERE item_title LIKE '%$tag'");
    }
});

test("news: an item at its address - canonical, description, NewsArticle; old and wrong addresses 301", function () {
    $pdo = TestPDO();
    [$tag, $ids] = newsSeed($pdo);
    try {
        $id = $ids["March One $tag"];
        $slug = "march-one-$tag";
        [$status, , $body] = newsRequest("path=%2Fview&newsID=$id", "/news/$id/$slug/");
        assertSame(200, $status);
        assertTrue(str_contains($body, "<title>March One $tag"), "title");
        assertTrue((bool)preg_match("#<link rel='canonical' href='[^']*//localhost/news/$id/$slug/'>#", $body), "canonical");
        assertTrue(str_contains($body, "content='Text of March One $tag with some words in it'"), "description");
        assertTrue((bool)preg_match('#"@type"\s*:\s*"NewsArticle"#', $body), "NewsArticle linked data");
        assertTrue(str_contains($body, "Text of March One $tag"), "content");
        assertTrue(!str_contains($body, "Text of March Two $tag"), "other items in the item view");

        [$status, $headers] = newsRequest("newsID=$id", "/news/index.php?newsID=$id");
        assertSame(301, $status, "old address");
        assertTrue(str_ends_with($headers["location"] ?? "", "/news/$id/$slug/"), "old address: " . ($headers["location"] ?? ""));
        [$status, $headers] = newsRequest("path=%2Fview&newsID=$id", "/news/$id/old-title/");
        assertSame(301, $status, "wrong slug");
        [$status, $headers] = newsRequest("path=%2Fview&newsID=999999", "/news/999999/x/");
        assertSame(302, $status, "unknown item");
        assertTrue(str_ends_with($headers["location"] ?? "", "/news/"), "unknown item to the module");
    }
    finally {
        $pdo->exec("DELETE FROM news_items WHERE item_title LIKE '%$tag'");
    }
});

test("news: archive month - only its items, old ?year=&month= 301, a wrong month to the module", function () {
    $pdo = TestPDO();
    [$tag, $ids] = newsSeed($pdo);
    try {
        [$status, , $body] = newsRequest("path=%2Farchive&year=2026&month=3", "/news/archive/2026/3/");
        assertSame(200, $status);
        assertTrue(str_contains($body, "Text of March One $tag") && str_contains($body, "Text of March Two $tag"), "the month items");
        assertTrue(!str_contains($body, "Text of April One $tag"), "an item of another month");
        preg_match("#<link rel='canonical'[^>]*>#", $body, $found);
        assertTrue((bool)preg_match("#<link rel='canonical' href='[^']*//localhost/news/archive/2026/3/'>#", $body), "canonical: " . ($found[0] ?? "none"));

        [$status, $headers] = newsRequest("year=2026&month=3", "/news/index.php?year=2026&month=3");
        assertSame(301, $status, "old archive address");
        assertTrue(str_ends_with($headers["location"] ?? "", "/news/archive/2026/3/"), "old archive: " . ($headers["location"] ?? ""));
        [$status] = newsRequest("path=%2Farchive&year=2026&month=13", "/news/archive/2026/13/");
        assertSame(302, $status, "wrong month");
    }
    finally {
        $pdo->exec("DELETE FROM news_items WHERE item_title LIKE '%$tag'");
    }
});

test("news: the dates are the dates of the items (not the day before - time zone)", function () {
    $pdo = TestPDO();
    [$tag, $ids] = newsSeed($pdo);
    try {
        $id = $ids["April One $tag"];
        [, , $body] = newsRequest("path=%2Fview&newsID=$id", "/news/$id/april-one-$tag/");
        assertTrue((bool)preg_match("#class='date'>2 \\w+ 2026<#u", $body), "date of 2026-04-02");
    }
    finally {
        $pdo->exec("DELETE FROM news_items WHERE item_title LIKE '%$tag'");
    }
});
