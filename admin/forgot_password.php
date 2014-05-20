<?php
include_once("session.php");
include_once("lib/input/InputFactory.php");

include_once("lib/pages/AdminLoginPage.php");

include_once("lib/beans/AdminUsersBean.php");

include_once("lib/mailers/ForgotAdminPasswordMailer.php");

include_once("lib/auth/Authenticator.php");

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
	  $fpm = new ForgotAdminPasswordMailer($fp->getValue(), $random_pass, SITE_DOMAIN.SITE_ROOT."admin/login.php");
	  $db = DBDriver::factory();
	  try {
			$db->transaction();
			 
			$fpm->send();

			$userID = $users->email2id($fp->getValue());
			$update_row["password"] = md5($random_pass);
			if (!$users->updateRecord($userID, $update_row, $db)) throw new Exception("Unable to update records: ".$db->getError());

			$db->commit();
			Session::set("alert", tr("Your new password was sent to this email").": ".$fp->getValue());
	  }
	  catch (Exception $e) {
		  $db->rollback();
		  Session::set("alert", "Error: ".$e->getMessage());
	  }

  }

}

$page->beginPage();


$page->setPreferredTitle("Forgot Password");

echo "<div class='login_component'>";

//   echo "<div style='float:left'>";
//   echo "<img src='".SITE_ROOT."admin/pics/admin_logo.png'>";
//   echo "</div>";

  echo "<span class='inner'>";

  echo "<span class='caption'>DTI Training CMS</span>";
  
  echo "<BR><BR>";
  echo tr("Input the email you have used on the time of registration");
  echo "<BR><BR>";

  echo "<form method=post>";


  $ic->render();

  echo "<a class='DefaultButton ' href='".SITE_ROOT."admin/login.php'>Back</a>";
  echo "<button type=submit class='DefaultButton admin_button orange'>Send</button>";
  /*
  StyledButton::DefaultButton()->drawSubmit("Send");*/

  echo "<input type=hidden value='1' name='request_password'>";

  echo "</form>";
  echo "</span>";


echo "</div>";



$page->finishPage();
?>