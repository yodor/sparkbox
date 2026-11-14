<?php
/**
 * URL versions: URL::setVersion() with FileVersion (static files) and StorageVersion (storage rows)
 */
include_once("utils/url/URL.php");
include_once("utils/url/FileVersion.php");
include_once("storage/StorageVersion.php");
include_once(__DIR__ . "/../harness/http.php");

test("FileVersion: base36 file time below the document root, empty for other paths", function () {
    $root = sys_get_temp_dir() . "/sparkbox-tests-docroot";
    @mkdir("$root/css", 0777, true);
    file_put_contents("$root/css/a.css", "body{}");
    touch("$root/css/a.css", 1700000000);
    $_SERVER["DOCUMENT_ROOT"] = $root;

    assertSame(base_convert("1700000000", 10, 36), (new FileVersion("/css/a.css"))->token());
    assertSame("", (new FileVersion("/css/missing.css"))->token());
    assertSame("", (new FileVersion("css/a.css"))->token(), "relative path");
    assertSame("", (new FileVersion("//cdn.example.com/a.css"))->token(), "protocol relative");
});

test("URL::setVersion adds v, keeps other parameters; no token adds nothing", function () {
    $_SERVER["DOCUMENT_ROOT"] = sys_get_temp_dir() . "/sparkbox-tests-docroot";
    $url = new URL("/css/a.css?x=1");
    assertTrue($url->setVersion(new FileVersion("/css/a.css")));
    assertSame(base_convert("1700000000", 10, 36), $url->getVersion());
    assertTrue(str_contains($url->toString(), "x=1"), $url->toString());

    $none = new URL("/css/missing.css");
    assertTrue(!$none->setVersion(new FileVersion("/css/missing.css")));
    assertSame("", $none->getVersion());
});

test("StorageVersion as IVersion: the marker time as the URL version", function () {
    $file = new StorageVersion("PhotosBean", 4242);
    assertTrue($file->token() !== "");
    $url = new URL("/storage.php");
    assertTrue($url->setVersion($file));
    assertSame($file->token(), $url->getVersion());
});

test("page CSS and JS links carry the file version, external links stay as given", function () {
    [, , $body] = httpRequest("assets.php");
    assertTrue((bool)preg_match("#href='/sparkfront/css/[A-Za-z]+\\.css\\?v=[0-9a-z]+'#", $body), "no versioned CSS link");
    assertTrue((bool)preg_match("#src='/sparkfront/js/[A-Za-z.]+\\.js\\?v=[0-9a-z]+'#", $body), "no versioned JS link");
    //unchanged apart from the HTML attribute escaping of '&'
    assertTrue(str_contains($body, "href='https://fonts.example.com/css?family=A:wght@400;700&amp;display=swap'"), "external link changed");
});
