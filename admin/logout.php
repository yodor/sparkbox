<?php
include_once("session.php");
include_once("lib/auth/AdminAuthenticator.php");

AdminAuthenticator::logout();
if (isset($_SESSION["upload_control"])) {
  unset($_SESSION["upload_control"]);
}
if (isset($_SESSION["upload_control_removed"])) {
  unset($_SESSION["upload_control_removed"]);
}

header("Location: ".SITE_ROOT."admin/");
exit;

?>
