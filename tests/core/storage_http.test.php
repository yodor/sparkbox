<?php
/**
 * storage.php end to end through php-cgi: versioned URLs, cache headers, 304, redirects of outdated
 * versions, slug URLs, Content-Disposition, class name validation.
 */
include_once(__DIR__ . "/../harness/http.php");
include_once("storage/FileStorageObject.php");
include_once("storage/ImageStorageObject.php");

function seedDoc(string $content) : int
{
    $object = new FileStorageObject();
    $object->setFilename("a.txt");
    $object->buffer()->setData($content);
    $pdo = TestPDO();
    //zero date on purpose - the version must not depend on the date column
    $pdo->exec("SET SESSION sql_mode=''");
    $pdo->prepare("INSERT INTO docs (data, date_upload) VALUES (?, '0000-00-00 00:00:00')")->execute(array(serialize($object)));
    return (int)$pdo->lastInsertId();
}

function seedPic() : int
{
    if (!function_exists("imagecreatetruecolor")) skip("gd not available");
    $image = imagecreatetruecolor(200, 120);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 40, 40));
    ob_start();
    imagepng($image);
    $object = new ImageStorageObject();
    $object->setFilename("red.png");
    $object->buffer()->setData(ob_get_clean());
    $pdo = TestPDO();
    $pdo->prepare("INSERT INTO pics (photo) VALUES (?)")->execute(array(serialize($object)));
    return (int)$pdo->lastInsertId();
}

function storageURL(string $class, int $id, string $type = "file", bool $slug = false, string $name = "") : string
{
    [$status, , $body] = httpRequest("url.php", http_build_query(array("class" => $class, "id" => $id, "type" => $type, "slug" => $slug ? 1 : 0, "name" => $name)));
    assertSame(200, $status, "url.php");
    return trim($body);
}

function queryOf(string $url) : string
{
    return substr($url, strpos($url, "?") + 1);
}

test("versioned URL: immutable, served from cache on the second request", function () {
    $id = seedDoc("version one");
    $query = queryOf(storageURL("DocsBean", $id));
    assertTrue(str_contains($query, "v="), "no version in $query");
    [$status, $headers, $body] = httpRequest("storage.php", $query);
    assertSame(200, $status);
    assertSame("version one", $body);
    assertTrue(str_contains($headers["cache-control"], "immutable"), $headers["cache-control"]);
    [, $headers] = httpRequest("storage.php", $query);
    assertSame("SparkCache", $headers["x-tag"] ?? "");
});

test("URL without version gets the one day rule", function () {
    $id = seedDoc("plain");
    [, $headers] = httpRequest("storage.php", "StorageRequest=1&cmd=data&class=DocsBean&id=$id");
    assertTrue(str_contains($headers["cache-control"], "max-age=86400"), $headers["cache-control"]);
});

test("matching ETag answers 304", function () {
    $id = seedDoc("etag");
    $query = queryOf(storageURL("DocsBean", $id));
    [, $headers] = httpRequest("storage.php", $query);
    [$status] = httpRequest("storage.php", $query, array("If-None-Match" => $headers["etag"]));
    assertSame(304, $status);
});

test("update through the bean: new URL, old URL redirects 301 to it", function () {
    $id = seedDoc("before");
    $old = storageURL("DocsBean", $id);
    sleep(1);
    [, , $body] = httpRequest("update.php", "class=DocsBean&id=$id&content=after");
    assertSame("updated", trim($body));
    $new = storageURL("DocsBean", $id);
    assertTrue($old !== $new, "URL did not change");
    [$status, $headers] = httpRequest("storage.php", queryOf($old));
    assertSame(301, $status);
    [, , $body] = httpRequest("storage.php", queryOf($headers["location"]));
    assertSame("after", $body);
});

test("slug URL carries the version as a path segment", function () {
    $id = seedDoc("slug");
    $url = storageURL("DocsBean", $id, "file", true, "Ябълка Голяма");
    assertTrue((bool)preg_match('#/assets/data/DocsBean/' . $id . '/[0-9a-z]+/[a-z0-9-]+\.' . $id . '$#', $url), $url);
    $route = substr($url, strpos($url, "/assets/") + 8);
    [$status, $headers, $body] = httpRequest("storage.php", "route=" . $route, array(), $url);
    assertSame(200, $status);
    assertSame("slug", $body);
    assertTrue(str_contains($headers["cache-control"], "immutable"));
    assertTrue(str_contains($headers["content-disposition"], ".$id.txt"), $headers["content-disposition"]);
});

test("image slug: webp output and file name", function () {
    $id = seedPic();
    $url = storageURL("PicsBean", $id, "image", true, "Червена снимка");
    $route = substr($url, strpos($url, "/assets/") + 8);
    [$status, $headers, $body] = httpRequest("storage.php", "route=" . $route, array(), $url);
    assertSame(200, $status);
    assertSame("image/webp", $headers["content-type"]);
    assertSame("RIFF", substr($body, 0, 4));
    assertTrue(str_contains($headers["content-disposition"], ".$id.webp"), $headers["content-disposition"]);
});

test("class names are validated before any filesystem access", function () {
    $target = sys_get_temp_dir() . "/sparkbox-tests-traversal";
    exec("rm -rf " . escapeshellarg($target));
    httpRequest("storage.php", "StorageRequest=1&cmd=data&class=" . urlencode("../../../../../.." . $target) . "&id=1");
    assertTrue(!is_dir($target), "directory created outside the cache");
});
