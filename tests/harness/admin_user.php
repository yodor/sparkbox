<?php
/**
 * Test only - creates (or replaces) an administrator account in the test database.
 * Runs only through php-cgi started by the test harness (SPARK_TESTS=1).
 *   admin_user.php?email=...&password=...  → prints CREATED
 */
if (!str_starts_with(PHP_SAPI, "cgi") || getenv("SPARK_TESTS") !== "1") {
    exit;
}

include_once("beans/AdminUsersBean.php");
include_once("auth/PasswordHash.php");

$email = (string)($_GET["email"] ?? "");
$users = new AdminUsersBean();
$existing = $users->getResult("email", $email);
if ($existing) $users->delete((int)$existing[$users->key()]);

$users->insert(array("email" => $email, "password" => (new PasswordHash((string)($_GET["password"] ?? "")))->getValue(),
    "context" => "", "access_level" => "Full Access", "fullname" => "Test Admin"));
echo "CREATED";
