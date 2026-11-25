<?php
/**
 * The session starts at the first write: a visitor who only reads pages gets no session cookie (pages stay
 * cacheable, no session file and lock per request); a login page stores its token and starts it
 */
include_once(__DIR__ . "/../harness/http.php");
include_once("utils/Session.php");

function sessionCookies(array $headers) : int
{
    $count = 0;
    foreach ((array)($headers["set-cookie"] ?? array()) as $line) {
        if (str_starts_with((string)$line, Session::Name() . "=")) $count++;
    }
    return $count;
}

test("a page that only reads starts no session", function () {
    [$status, $headers] = httpRequest("index.php", "", array(), "/");
    assertSame(200, $status);
    assertSame(0, sessionCookies($headers), "no session cookie for the home");

    [$status, $headers] = httpRequest("news/index.php", "", array(), "/news/");
    assertSame(200, $status);
    assertSame(0, sessionCookies($headers), "no session cookie for the news");
});

test("a login page stores its token - the session starts", function () {
    [$status, $headers, $body] = httpRequest("admin/index.php", "path=%2Flogin", array(), "/admin/login");
    assertSame(200, $status);
    assertSame(1, sessionCookies($headers), "the session cookie of the login token");
    assertTrue((bool)preg_match("#name='token' value='[0-9a-f]{32}'#", $body), "the token on the page");
});

/**
 * The value of the cookie '$name' set by a response, or ""
 */
function setCookieValue(array $headers, string $name) : string
{
    foreach ((array)($headers["set-cookie"] ?? array()) as $line) {
        if (preg_match("#^" . preg_quote($name, "#") . "=([^;]*)#", (string)$line, $m)) return $m[1];
    }
    return "";
}

test("the session cookie is the site's own: the SparkBox name, no Domain attribute", function () {
    [, $headers] = httpRequest("admin/index.php", "path=%2Flogin", array(), "/admin/login");
    $line = "";
    foreach ((array)($headers["set-cookie-raw"] ?? array()) as $cookie) {
        if (str_starts_with((string)$cookie, Session::Name() . "=")) $line = (string)$cookie;
    }
    assertTrue(str_starts_with($line, "sb_session="), "the SparkBox name: " . $line);
    assertTrue(str_contains(strtolower($line), "samesite=strict") && str_contains(strtolower($line), "httponly"), "strict, httponly: " . $line);
    assertTrue(!preg_match("#;\\s*domain=#i", $line), "host-only (a parent / sub domain site does not get it): " . $line);
});

test("a PHPSESSID cookie (another application's, or of the SparkBox before 198) is not read", function () {
    [, $headers] = httpRequest("index.php", "", array("Cookie" => "PHPSESSID=" . str_repeat("a1b2c3d4", 4)), "/");
    assertSame(0, sessionCookies($headers), "no session for a public page");
    [, $headers] = httpRequest("admin/index.php", "path=%2Flogin", array("Cookie" => "PHPSESSID=" . str_repeat("a1b2c3d4", 4)), "/admin/login");
    $id = setCookieValue($headers, Session::Name());
    assertTrue($id !== "" && $id !== str_repeat("a1b2c3d4", 4), "a new session of its own: " . $id);
});
