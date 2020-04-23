<?php
include_once("session.php");
include_once("lib/input/DataInputFactory.php");
include_once("lib/pages/AdminLoginPage.php");

include_once("lib/input/validators/EmailValidator.php");
include_once("lib/beans/AdminUsersBean.php");


include_once("lib/mailers/ForgotPasswordMailer.php");

$page = new AdminLoginPage();

$ub = new AdminUsersBean();

$fp = DataInputFactory::Create(DataInputFactory::EMAIL, "email", "Email", 1);
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

        $random_pass = Authenticator::RandomToken(8);
        $fpm = new ForgotPasswordMailer($fp->getValue(), $random_pass, SITE_DOMAIN . SITE_ROOT . "admin/login.php");
        $db = DBDriver::Get();
        try {
            $db->transaction();

            $fpm->send();

            $userID = $users->email2id($fp->getValue());
            $update_row["password"] = md5($random_pass);
            if (!$users->update($userID, $update_row, $db)) throw new Exception("Unable to update records: " . $db->getError());

            $db->commit();
            Session::Set("alert", tr("Your new password was sent to this email") . ": " . $fp->getValue());
            header("Location: login.php");
            exit;
        }
        catch (Exception $e) {
            $db->rollback();
            Session::Set("alert", "Error: " . $e->getMessage());
        }

    }

}

$page->startRender();


$page->setPreferredTitle("Forgot Password");

echo "<div class='login_component'>";


echo "<span class='inner'>";

echo "<span class='caption'>Demo Administration</span>";


echo "<BR><BR>";
echo tr("Input the email you have used on the time of registration");
echo "<BR><BR>";

echo "<form method=post>";
$ic->render();

StyledButton::DefaultButton()->renderSubmit("Send");


echo "<input type=hidden value='1' name='request_password'>";

echo "</form>";

echo "</span>";


echo "</div>";


$page->finishRender();
?>
