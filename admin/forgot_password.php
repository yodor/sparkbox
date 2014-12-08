<?php
include_once("session.php");
include_once("lib/input/InputFactory.php");
include_once("lib/pages/AdminLoginPage.php");

include_once("lib/input/validators/EmailValidator.php");
include_once("lib/beans/AdminUsersBean.php");


include_once("lib/mailers/ForgotPasswordMailer.php");

$page = new AdminLoginPage();

$ub = new AdminUsersBean();

$fp = InputFactory::CreateField(InputFactory::EMAIL, "email", "Email", 1);
$ic = new InputComponent();
$ic->setField($fp);

if (isset($_POST["request_password"])) {
	
  $fp->loadPostData($_POST);
  $fp->validate();

  if (!$fp->haveError()) {

	  if (!$ub->emailExists($fp->getValue())) {
		  $fp->setError(tr("This email is not registered with us."));
	  }
  }
  if (!$fp->haveError()) {

	  $users = new AdminUsersBean();
	  
	  $random_pass = Authenticator::generateRandomAuth(8);
	  $fpm = new ForgotPasswordMailer($fp->getValue(), $random_pass, SITE_DOMAIN.SITE_ROOT."admin/login.php");
	  $db = DBDriver::factory();
	  try {
			$db->transaction();
			 
			$fpm->send();

			$userID = $users->email2id($fp->getValue());
			$update_row["password"] = md5($random_pass);
			if (!$users->updateRecord($userID, $update_row, $db)) throw new Exception("Unable to update records: ".$db->getError());

			$db->commit();
			Session::set("alert", tr("Your new password was sent to this email").": ".$fp->getValue());
			header("Location: login.php");
			exit;
	  }
	  catch (Exception $e) {
		  $db->rollback();
		  Session::set("alert", "Error: ".$e->getMessage());
	  }

  }

}

$page->beginPage();


$page->heading="Forgot Password Page";

echo "<div align=center>";

echo "<div style='width:420px;'>";

echo "<BR><BR>";
echo tr("Input the email you have used on the time of registration");
echo "<BR><BR>";

echo "<form method=post>";
$ic->render();

StyledButton::DefaultButton()->drawSubmit("Send");


echo "<input type=hidden value='1' name='request_password'>";

echo "</form>";
echo "</div></div>";

$page->finishPage();
?>