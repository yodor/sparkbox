<?php
/**
 * The admin 'forgot password': a one-time link by mail (AdminPasswordResetsBean), the password changes only from the
 * link - its own administrator account (reset@core.test), so the other admin tests keep theirs.
 */
include_once(__DIR__ . "/../harness/http.php");

function resetAdminRequest(string $path, array $post = array(), string $query = "") : array
{
    $options = (count($post) > 0) ? array("post" => $post) : array();
    $full = "path=" . urlencode($path) . (($query !== "") ? "&" . $query : "");
    return httpRequest("admin/index.php", $full, array(), "/admin/index.php?" . $full, $options);
}

function resetAdminLogin(string $password) : bool
{
    [, , $body] = httpRequest("admin_login.php", http_build_query(array("email" => "reset@core.test", "password" => $password)), array(), "",
        array("script_file" => realpath(TESTS_PATH . "/harness/admin_login.php")));
    return str_contains($body, "LOGGED_IN");
}

test("admin forgot password: a link by mail, the password changes only from the link, once", function () {
    [, , $body] = httpRequest("admin_user.php", "email=reset@core.test&password=old-pass-123", array(), "",
        array("script_file" => realpath(TESTS_PATH . "/harness/admin_user.php")));
    assertTrue(str_contains($body, "CREATED"), "admin user not created");
    //the table: created by its bean at the first request

    //the request: the same answer, the password unchanged, a mail with the link
    @unlink(TestMailLog());
    [$status, $headers] = resetAdminRequest("/password", array("email" => "reset@core.test", "SubmitForm" => "InputForm"));
    assertSame(302, $status, "request");
    assertTrue(str_ends_with($headers["location"] ?? "", "/admin/login"), "back to the login");
    assertTrue(resetAdminLogin("old-pass-123"), "the password changed before the link was used");
    $mail = (string)@file_get_contents(TestMailLog());
    assertTrue((bool)preg_match("#/admin/password/reset\?token=([0-9a-f]{64})#", $mail, $m), "no link in the mail");
    $token = $m[1];

    //an unknown e-mail: the same answer, no mail
    @unlink(TestMailLog());
    [$status] = resetAdminRequest("/password", array("email" => "nobody@core.test", "SubmitForm" => "InputForm"));
    assertSame(302, $status);
    assertSame("", (string)@file_get_contents(TestMailLog()), "a mail for an unknown e-mail");

    //the link: the form; a wrong token goes back to the request page
    [$status, , $page] = resetAdminRequest("/password/reset", array(), "token=$token");
    assertSame(200, $status, "the reset page");
    assertTrue(str_contains($page, "name='password_repeat'"), "the form");
    [$status, $headers] = resetAdminRequest("/password/reset", array(), "token=" . str_repeat("0", 64));
    assertSame(302, $status, "a wrong token");

    //too short, not the same - refused; then saved
    resetAdminRequest("/password/reset", array("password" => "short", "password_repeat" => "short", "SubmitForm" => "PasswordResetForm"), "token=$token");
    resetAdminRequest("/password/reset", array("password" => "new-pass-123", "password_repeat" => "new-pass-124", "SubmitForm" => "PasswordResetForm"), "token=$token");
    assertTrue(resetAdminLogin("old-pass-123"), "a refused password was saved");
    [$status, $headers] = resetAdminRequest("/password/reset", array("password" => "new-pass-123", "password_repeat" => "new-pass-123", "SubmitForm" => "PasswordResetForm"), "token=$token");
    assertSame(302, $status, "saved");
    assertTrue(resetAdminLogin("new-pass-123") && !resetAdminLogin("old-pass-123"), "the new password");

    //the link works once
    [$status] = resetAdminRequest("/password/reset", array(), "token=$token");
    assertSame(302, $status, "the used link");
});
