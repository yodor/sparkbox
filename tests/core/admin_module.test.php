<?php
/**
 * The admin module without sparkstore - core part only (tests/app/admin -> modules/admin)
 */
include_once(__DIR__ . "/../harness/http.php");

function coreAdminCookies() : string
{
    static $cookies = null;
    if ($cookies !== null) return $cookies;

    [, , $body] = httpRequest("admin_user.php", "email=admin@core.test&password=core-pass", array(), "",
        array("script_file" => realpath(TESTS_PATH . "/harness/admin_user.php")));
    assertTrue(str_contains($body, "CREATED"), "admin user not created: " . substr(strip_tags($body), 0, 200));

    [, $headers, $body] = httpRequest("admin_login.php", "email=admin@core.test&password=core-pass", array(), "",
        array("script_file" => realpath(TESTS_PATH . "/harness/admin_login.php")));
    assertTrue(str_contains($body, "LOGGED_IN"), "login failed: " . substr(strip_tags($body), 0, 200));
    $cookies = implode("; ", $headers["set-cookie"]);
    return $cookies;
}

function coreAdmin(string $path, bool $loggedIn = true) : array
{
    $headers = $loggedIn ? array("Cookie" => coreAdminCookies()) : array();
    return httpRequest("admin/index.php", "path=" . urlencode($path), $headers, "/admin/index.php?path=" . urlencode($path));
}

test("protected path redirects to the module login, login page is public", function () {
    [$status, $headers] = coreAdmin("/home", false);
    assertSame(302, $status);
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/login"), $headers["location"] ?? "no location");
    [$status] = coreAdmin("/login", false);
    assertSame(200, $status);
});

test("home, content and settings render with the core menu only", function () {
    foreach (array("/home", "/content", "/settings") as $path) {
        [$status, $headers, $body] = coreAdmin($path);
        assertSame(200, $status, "$path" . (isset($headers["location"]) ? " → " . $headers["location"] : ""));
        assertTrue(!str_contains($body, "Fatal error") && !str_contains($body, "<b>Warning</b>"), "$path has errors");
    }
    [, , $body] = coreAdmin("/home");
    assertTrue(str_contains($body, ">Content<") && str_contains($body, ">Settings<"), "core menu missing");
    assertTrue(!str_contains($body, ">Store<"), "store menu in a non-store site");
});

test("logout returns to login", function () {
    [$status, $headers] = coreAdmin("/logout");
    assertSame(302, $status);
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/login"));
});
