<?php
include_once("mailers/Mailer.php");


class ForgotPasswordMailer extends Mailer
{


    public function __construct($email, $random_pass, $login_url)
    {

        $this->subject = "Forgot Password Request at: " . SITE_TITLE;

        $this->to = $email;


        // 		$server_file = INSTALL_PATH."/emails/admin_forgot_password.html";
        //
        // 		$this->setTemplateHTML($server_file);
        //
        // 		$this->replaceBody("Base HREF", SITE_URL.SITE_ROOT."system/token/");


        $message = "";
        $message .= "Hello,\r\n";
        $message .= "This email is sent in relation to your forgot password request at " . SITE_URL . "\r\n";
        $message .= "Your new password is: $random_pass\r\n";
        $message .= "\r\n";
        $message .= "Sincerely,\r\n";
        $message .= SITE_TITLE;
        $this->body = $message;


    }

}

?>