<?php
/**
 * The session starts at the first write: a visitor who only reads pages gets no session cookie (pages stay
 * cacheable, no session file and lock per request); a login page stores its token and starts it
 */
include_once(__DIR__ . "/../harness/http.php");

function sessionCookies(array $headers) : int
{
    $count = 0;
    foreach ((array)($headers["set-cookie"] ?? array()) as $line) {
        if (str_starts_with((string)$line, session_name() . "=")) $count++;
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
