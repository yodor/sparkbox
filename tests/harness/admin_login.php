<?php
/**
 * Test only - logs in an administrator for the HTTP level tests without the browser login challenge.
 * The password is still verified. Runs only through php-cgi started by the test harness (SPARK_TESTS=1),
 * it lives outside the site document root and is never reachable from a web server.
 *   admin_login.php?email=...&password=...  → prints LOGGED_IN and sets the session cookies
 */
if (!str_starts_with(PHP_SAPI, "cgi") || getenv("SPARK_TESTS") !== "1") {
    exit;
}

include_once("auth/AdminAuthenticator.php");

class TestAdminAuthenticator extends AdminAuthenticator
{
    public function verifyTokenHMAC(PasswordHash $hash, string $client_hmac) : void
    {
        //the browser challenge is not part of these tests
    }
}

Session::Start();
$auth = new TestAdminAuthenticator();
$auth->login((string)($_GET["email"] ?? ""), (string)($_GET["password"] ?? ""), "");
echo "LOGGED_IN";
