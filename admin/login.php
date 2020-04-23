<?php
include_once("session.php");
include_once("lib/pages/AdminLoginPage.php");
include_once("lib/auth/AdminAuthenticator.php");
include_once("lib/handlers/AuthenticatorRequestHandler.php");

include_once("lib/forms/AuthForm.php");
include_once("lib/forms/renderers/AuthFormRenderer.php");

$page = new AdminLoginPage();

$auth = new AdminAuthenticator();
$auth->setLoginURL(SITE_ROOT . "admin/login.php");

$req = new AuthenticatorRequestHandler($auth, "doLogin");
$req->setCancelUrl($auth->getLoginURL());
$req->setSuccessUrl(SITE_ROOT . "admin/index.php");

RequestController::addRequestHandler($req);

$af = new AuthForm();

$afr = new AuthFormRenderer();


$afr->setAttribute("name", "auth");
$afr->setForm($af);
$afr->setAuthContext($auth->name());
$afr->getSubmitButton()->setClassName("admin_button orange");


$page->startRender();
$page->setPreferredTitle("Login");

echo "<div class='login_component'>";

//   echo "<div style='float:left'>";
//   echo "<img src='".SITE_ROOT."admin/pics/admin_logo.png'>";
//   echo "</div>";

echo "<span class='inner'>";

echo "<span class='caption'>Demo Administration</span>";
$afr->renderForm($af);
echo "</span>";


echo "</div>";

$page->finishRender();
?>

