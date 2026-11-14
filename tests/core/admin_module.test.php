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


test("content sections render (lists and add forms) without the store", function () {
    foreach (array("/content/pages", "/content/news", "/content/faq", "/content/faq/sections", "/content/menus") as $path) {
        foreach (array("", "&editID=-1") as $edit) {
            [$status, $headers, $body] = httpRequest("admin/index.php", "path=" . urlencode($path) . $edit,
                array("Cookie" => coreAdminCookies()), "/admin/index.php?path=" . urlencode($path) . $edit);
            $label = $path . $edit;
            assertSame(200, $status, $label . (isset($headers["location"]) ? " → " . $headers["location"] : ""));
            foreach (array("Fatal error", "<b>Warning</b>", "<b>Deprecated</b>", "SQLSTATE", "Error:/") as $marker) {
                assertTrue(!str_contains($body, $marker), "$label contains '$marker'");
            }
        }
    }
});

test("early responder answers without creating the page", function () {
    //the pages gallery path file redirects without a page ID - an early responder answers before it runs
    [$status, , $body] = httpRequest("admin/index.php", "path=%2Fcontent%2Fpages%2Fgallery&JSONRequest=1&responder=TemplateHelpResponder&type=fetch",
        array("Cookie" => coreAdminCookies()), "/admin/index.php?path=%2Fcontent%2Fpages%2Fgallery");
    assertSame(200, $status);
    assertTrue(json_decode($body) !== null, "not JSON: " . substr($body, 0, 120));
});

test("responders that are not early still go through the page", function () {
    //DeleteItemResponder is not a JSON responder - the path file runs and redirects (no page ID)
    [$status] = httpRequest("admin/index.php", "path=%2Fcontent%2Fpages%2Fgallery&JSONRequest=1&responder=MCEImageBrowserResponder&type=find",
        array("Cookie" => coreAdminCookies()), "/admin/index.php?path=%2Fcontent%2Fpages%2Fgallery");
    assertSame(302, $status, "MCE browser must not be processed early");
});

test("saving a form: add a FAQ section and edit it (POST, redirect, database)", function () {
    $post = function (string $query, array $fields) : array {
        return httpRequest("admin/index.php", $query, array("Cookie" => coreAdminCookies()), "/admin/index.php?" . $query,
            array("post" => $fields + array("SubmitForm" => "FAQSectionInputForm")));
    };

    [$status, $headers] = $post("path=%2Fcontent%2Ffaq%2Fsections&editID=-1", array("section_name" => "Shipping"));
    assertSame(302, $status, "no redirect after add");
    assertTrue((bool)preg_match("/editID=(\\d+)/", $headers["location"] ?? "", $m), "redirect without editID: " . ($headers["location"] ?? ""));
    $id = (int)$m[1];
    assertSame("Shipping", TestPDO()->query("SELECT section_name FROM faq_sections WHERE fqsID=$id")->fetchColumn());

    [$status] = $post("path=%2Fcontent%2Ffaq%2Fsections&editID=$id", array("section_name" => "Delivery"));
    assertSame(302, $status, "no redirect after update");
    assertSame("Delivery", TestPDO()->query("SELECT section_name FROM faq_sections WHERE fqsID=$id")->fetchColumn());
});

test("saving a form with a missing required field shows the error and writes nothing", function () {
    $before = (int)TestPDO()->query("SELECT COUNT(*) FROM faq_sections")->fetchColumn();
    [$status, , $body] = httpRequest("admin/index.php", "path=%2Fcontent%2Ffaq%2Fsections&editID=-1", array("Cookie" => coreAdminCookies()),
        "/admin/index.php?path=%2Fcontent%2Ffaq%2Fsections&editID=-1", array("post" => array("section_name" => "", "SubmitForm" => "FAQSectionInputForm")));
    assertSame(200, $status);
    assertSame($before, (int)TestPDO()->query("SELECT COUNT(*) FROM faq_sections")->fetchColumn());
    assertTrue(str_contains($body, "error"), "no error shown");
});

//last - ends the session used by the tests above
test("logout returns to login", function () {
    [$status, $headers] = coreAdmin("/logout");
    assertSame(302, $status);
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/login"));
});
