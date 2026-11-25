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

test("a session of the legacy PHPSESSID cookie is carried over once; an unknown one is not taken", function () {
    [, $headers] = httpRequest("admin/index.php", "path=%2Flogin", array(), "/admin/login");
    $id = setCookieValue($headers, Session::Name());
    assertTrue($id !== "", "a session");

    //the same session under the old name: kept, moved to the SparkBox name, the old cookie removed
    [, $headers] = httpRequest("admin/index.php", "path=%2Flogin", array("Cookie" => "PHPSESSID=$id"), "/admin/login");
    assertSame($id, setCookieValue($headers, Session::Name()), "carried over with its id");
    $sent = array_filter($headers["set-cookie"], fn($c) => str_starts_with((string)$c, Session::Name() . "="));
    assertSame(1, count($sent), "one session cookie");
    $removed = array();
    foreach ((array)($headers["set-cookie-raw"] ?? array()) as $line) {
        if (!preg_match("#^PHPSESSID=deleted;.*expires=#i", (string)$line)) continue;
        $removed[] = preg_match("#;\\s*domain=([^;]+)#i", (string)$line, $m) ? strtolower(trim($m[1])) : "host-only";
    }
    sort($removed);
    assertSame(array(".localhost", "host-only"), $removed, "the PHPSESSID cookies removed: host-only and the old '.host' one");

    //an id this site does not know (another site's cookie): a new session, not that id
    $foreign = str_repeat("a1b2c3d4", 4);
    [, $headers] = httpRequest("admin/index.php", "path=%2Flogin", array("Cookie" => "PHPSESSID=$foreign"), "/admin/login");
    $new = setCookieValue($headers, Session::Name());
    assertTrue($new !== "" && $new !== $foreign, "a new session: " . $new);

    //two PHPSESSID cookies (a parent domain site's and this site's): which one is ours is unknown - a new session
    [, $headers] = httpRequest("admin/index.php", "path=%2Flogin", array("Cookie" => "PHPSESSID=$id; PHPSESSID=$foreign"), "/admin/login");
    $new = setCookieValue($headers, Session::Name());
    assertTrue($new !== "" && $new !== $id, "a new session for two legacy cookies: " . $new);
});
