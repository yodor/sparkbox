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

/**
 * Alerts of the page (showAlert calls)
 */
function coreAlerts(string $body) : string
{
    preg_match_all('/showAlert\(("(?:[^"\\\\]|\\\\.)*")\)/', $body, $m);
    return implode(" | ", array_map("json_decode", $m[1]));
}

test("login redirect: the module alert at the login page - none when the module sets it empty", function () {
    //the admin: a protected path → login with "Authorization Required"
    [, $headers] = coreAdmin("/home", false);
    [, , $login] = httpRequest("admin/index.php", "path=%2Flogin", array("Cookie" => implode("; ", $headers["set-cookie"])), "/admin/index.php?path=%2Flogin");
    assertTrue(str_contains(coreAlerts($login), "Authorization Required"), "default alert: " . coreAlerts($login));

    foreach (array("" => "", "Please log in" => "Please log in") as $alert => $expected) {
        [$status, $headers] = httpRequest("module_login.php", http_build_query(array("alert" => $alert)));
        assertSame(302, $status);
        assertTrue(str_ends_with($headers["location"] ?? "", "/admin/login"), "to login");
        [, , $login] = httpRequest("admin/index.php", "path=%2Flogin", array("Cookie" => implode("; ", $headers["set-cookie"])), "/admin/index.php?path=%2Flogin");
        assertSame($expected, coreAlerts($login), "alert '$alert'");
    }
});

test("login redirect: the module loginPath", function () {
    [$status, $headers] = httpRequest("module_login.php", http_build_query(array("alert" => "", "path" => "guest")));
    assertSame(302, $status);
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/guest"), "to the login path: " . ($headers["location"] ?? ""));
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
        array("Cookie" => coreAdminCookies()), "/admin/index.php?path=%2Fcontent%2Fpages%2Fgallery&JSONRequest=1&responder=TemplateHelpResponder&type=fetch");
    assertSame(200, $status);
    assertTrue(json_decode($body) !== null, "not JSON: " . substr($body, 0, 120));
});

test("responders that are not early still go through the page", function () {
    //DeleteItemResponder is not a JSON responder - the path file runs and redirects (no page ID)
    [$status] = httpRequest("admin/index.php", "path=%2Fcontent%2Fpages%2Fgallery&JSONRequest=1&responder=MCEImageBrowserResponder&type=find",
        array("Cookie" => coreAdminCookies()), "/admin/index.php?path=%2Fcontent%2Fpages%2Fgallery&JSONRequest=1&responder=MCEImageBrowserResponder&type=find");
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

function adminPost(string $query, array $fields, string $cookies = "") : array
{
    return httpRequest("admin/index.php", $query, array("Cookie" => ($cookies !== "" ? $cookies : coreAdminCookies())),
        "/admin/index.php?" . $query, array("post" => $fields));
}

function loginCookies(string $email, string $password) : string
{
    [, $headers, $body] = httpRequest("admin_login.php", http_build_query(array("email" => $email, "password" => $password)), array(), "",
        array("script_file" => realpath(TESTS_PATH . "/harness/admin_login.php")));
    return str_contains($body, "LOGGED_IN") ? implode("; ", $headers["set-cookie"]) : "";
}

test("settings sections render", function () {
    TestPDO()->exec("INSERT IGNORE INTO languages (langID, lang_code, language) VALUES (1, 'bgn', 'Bulgarian')");
    foreach (array("/settings/admins", "/settings/languages", "/settings/languages/translator") as $path) {
        foreach (array("", "&editID=-1") as $edit) {
            if ($edit && str_ends_with($path, "translator")) continue;
            [$status, $headers, $body] = httpRequest("admin/index.php", "path=" . urlencode($path) . $edit,
                array("Cookie" => coreAdminCookies()), "/admin/index.php?path=" . urlencode($path) . $edit);
            assertSame(200, $status, $path . $edit . (isset($headers["location"]) ? " → " . $headers["location"] : ""));
            foreach (array("Fatal error", "<b>Warning</b>", "<b>Deprecated</b>", "SQLSTATE", "Error:/") as $marker) {
                assertTrue(!str_contains($body, $marker), "$path$edit contains '$marker'");
            }
        }
    }
    [$status, , $body] = httpRequest("admin/index.php", "path=%2Fsettings%2Flanguages%2Ftranslator%2Fphrases&langID=1",
        array("Cookie" => coreAdminCookies()), "/admin/index.php?path=%2Fsettings%2Flanguages%2Ftranslator%2Fphrases&langID=1");
    assertSame(200, $status, "phrases");
    assertTrue(!str_contains($body, "Fatal error") && !str_contains($body, "<b>Warning</b>"), "phrases has errors");
});

test("new administrator: password stored as hash, can log in, suspended cannot", function () {
    [$status, $headers] = adminPost("path=%2Fsettings%2Fadmins&editID=-1", array(
        "email" => "editor@core.test", "fullname" => "Editor", "password" => "editor-pass", "password_compare" => "editor-pass",
        "access_level" => "Full Access", "SubmitForm" => "AdminUserForm"));
    assertSame(302, $status, "no redirect after add");
    $row = TestPDO()->query("SELECT userID, password FROM admin_users WHERE email='editor@core.test'")->fetch();
    assertTrue(is_array($row), "account not created");
    assertTrue($row["password"] !== "editor-pass" && password_get_info($row["password"])["algo"] !== null, "password not stored as hash");
    assertTrue(loginCookies("editor@core.test", "editor-pass") !== "", "new administrator cannot log in");

    //editing without a password keeps it
    adminPost("path=%2Fsettings%2Fadmins&editID=" . $row["userID"], array(
        "email" => "editor@core.test", "fullname" => "Editor Renamed", "password" => "", "password_compare" => "",
        "access_level" => "Full Access", "SubmitForm" => "AdminUserForm"));
    assertSame($row["password"], TestPDO()->query("SELECT password FROM admin_users WHERE userID=" . $row["userID"])->fetchColumn(), "empty password field replaced the password");

    TestPDO()->exec("UPDATE admin_users SET suspend=1 WHERE userID=" . $row["userID"]);
    assertSame("", loginCookies("editor@core.test", "editor-pass"), "suspended administrator logged in");
});

test("limited access administrator sees and opens only the sections of its roles", function () {
    [$status] = adminPost("path=%2Fsettings%2Fadmins&editID=-1", array(
        "email" => "limited@core.test", "fullname" => "Limited", "password" => "limited-pass", "password_compare" => "limited-pass",
        "access_level" => "Limited Access", "role" => array("Content"), "SubmitForm" => "AdminUserForm"));
    assertSame(302, $status, "limited account not created");
    $cookies = loginCookies("limited@core.test", "limited-pass");
    assertTrue($cookies !== "", "limited administrator cannot log in");

    [$status, , $body] = httpRequest("admin/index.php", "path=%2Fcontent", array("Cookie" => $cookies), "/admin/index.php?path=%2Fcontent");
    assertSame(200, $status, "content denied");
    assertTrue(!str_contains($body, ">Settings<"), "Settings shown in the menu");

    [$status, $headers] = httpRequest("admin/index.php", "path=%2Fsettings%2Fadmins", array("Cookie" => $cookies), "/admin/index.php?path=%2Fsettings%2Fadmins");
    assertSame(302, $status, "settings not denied");
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/home"), $headers["location"] ?? "");

    //early responders are behind the same check
    [$status] = httpRequest("admin/index.php", "path=%2Fsettings%2Flanguages%2Ftranslator&JSONRequest=1&responder=TemplateHelpResponder&type=fetch",
        array("Cookie" => $cookies), "/admin/index.php?path=%2Fsettings%2Flanguages%2Ftranslator");
    assertSame(302, $status, "early responder bypassed the access check");
});

test("translator menu lists the languages", function () {
    TestPDO()->exec("INSERT IGNORE INTO languages (langID, lang_code, language) VALUES (1, 'bgn', 'Bulgarian')");
    [, , $body] = httpRequest("admin/index.php", "path=%2Fhome", array("Cookie" => coreAdminCookies()), "/admin/index.php?path=%2Fhome");
    assertTrue(str_contains($body, "/admin/settings/languages/translator/phrases?langID=1"), "language link missing");
    assertTrue(str_contains($body, ">BGN<"), "language code missing");
});

test("forgot password works without the store and does not reveal registered emails", function () {
    [$status, , $body] = httpRequest("admin/index.php", "path=%2Fpassword", array(), "/admin/index.php?path=%2Fpassword");
    assertSame(200, $status, "password page");
    assertTrue(!str_contains($body, "Fatal error") && !str_contains($body, "<b>Warning</b>"), "password page has errors");
    [$status, $headers] = httpRequest("admin/index.php", "path=%2Fpassword", array(), "/admin/index.php?path=%2Fpassword",
        array("post" => array("email" => "nobody@core.test", "SubmitForm" => "InputForm")));
    assertSame(302, $status, "unknown email: no redirect");
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/login"), $headers["location"] ?? "");
});

//last - ends the session used by the tests above
/**
 * Admin page with extra query parameters
 */
function coreAdminQuery(string $path, array $params) : array
{
    $query = http_build_query(array_merge(array("path" => $path), $params));
    return httpRequest("admin/index.php", $query, array("Cookie" => coreAdminCookies()), "/admin/index.php?$query");
}

test("menu item editor: Choose Dynamic Page - the pages list in chooser mode, Preview, the chosen page address", function () {
    $pdo = TestPDO();
    //the dynamic pages table (the bean creates it)
    coreAdmin("/content/pages");
    $pdo->exec("INSERT INTO dynamic_pages (item_title, item_date, visible, content, keywords, position) VALUES ('Chooser Page', now(), 1, '<p>c</p>', 'chooser_test;', 1)");
    $id = (int)$pdo->lastInsertId();
    try {
        [$status, $headers, $editor] = coreAdminQuery("/content/menus", array("editID" => -1));
        assertSame(200, $status, "menu item editor: " . ($headers["location"] ?? ""));
        assertTrue((bool)preg_match("#href='(/admin/content/pages\\?chooser=[^']+)'#", $editor, $m), "Choose Dynamic Page link");
        $chooser = urldecode(html_entity_decode(explode("chooser=", $m[1])[1]));

        [$status, , $list] = coreAdminQuery("/content/pages", array("chooser" => $chooser));
        assertSame(200, $status, "pages list in chooser mode");
        assertTrue(str_contains($list, "Choose Page"), "Choose Page action");
        assertTrue((bool)preg_match("#href='([^']*page_id={$id}[^']*)'#", $list, $c), "Choose Page link of the page");
        assertTrue((str_contains(html_entity_decode($c[1]), "/admin/content/menus") || str_contains(html_entity_decode($c[1]), "path=%2Fcontent%2Fmenus")) && str_contains(html_entity_decode($c[1]), "editID=-1"), "back to the menu item editor: " . $c[1]);
        assertTrue((bool)preg_match("#href='[^']*/pages/$id/chooser-page/'[^>]*target='_blank'|target='_blank'[^>]*href='[^']*/pages/$id/chooser-page/'#", $list), "Preview of the page");

        //back with the page: its address in the link field
        [, , $editor] = coreAdminQuery("/content/menus", array("editID" => -1, "page_id" => $id));
        assertTrue(str_contains($editor, "value='/pages/$id/chooser-page/'"), "link of the chosen page");

        //a site in a folder of the document root (LOCAL '/app'): the link is stored from the site root, the menu adds LOCAL
        $query = http_build_query(array("path" => "/content/menus", "editID" => -1, "page_id" => $id));
        [, , $editor] = httpRequest("app/admin/index.php", $query, array("Cookie" => coreAdminCookies()), "/app/admin/index.php?$query",
            array("root" => realpath(TESTS_PATH), "include_path" => realpath(TESTS_PATH . "/../lib") . PATH_SEPARATOR . realpath(TESTS_PATH . "/app"),
                "prepend" => TESTS_PATH . "/harness/sparkboot.php"));
        assertTrue(str_contains($editor, "value='/pages/$id/chooser-page/'"), "link of the chosen page under a folder");

        //a chooser address outside the admin is refused
        [, , $list] = coreAdminQuery("/content/pages", array("chooser" => base64_encode("https://example.com/admin/x")));
        assertTrue(!str_contains($list, "Choose Page"), "chooser outside the admin");
        //without chooser: Preview only
        [, , $list] = coreAdmin("/content/pages");
        assertTrue(!str_contains($list, "Choose Page") && str_contains($list, "/pages/$id/chooser-page/"), "list without chooser");
    }
    finally {
        $pdo->exec("DELETE FROM dynamic_pages WHERE dpID = $id");
    }
});

test("logout returns to login", function () {
    [$status, $headers] = coreAdmin("/logout");
    assertSame(302, $status);
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/login"));
});
