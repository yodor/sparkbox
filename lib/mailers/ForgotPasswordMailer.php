<?php
include_once("mailers/Mailer.php");

class ForgotPasswordMailer extends Mailer
{

    /**
     * ForgotPasswordMailer constructor.
     * @param $email string Recipient email
     * @param $random_pass string the password
     * @param $login_url string url of the login page
     */
    public function __construct(string $email, string $random_pass, string $login_url)
    {
        parent::__construct();

        $this->subject = tr("Forgot Password Request at: ") . SITE_TITLE;

        $this->to = $email;

        // 		$server_file = INSTALL_PATH."/emails/admin_forgot_password.html";
        //
        // 		$this->setTemplateHTML($server_file);
        //
        // 		$this->replaceBody("Base HREF", SITE_URL.LOCAL."system/token/");

        $message = "";
        $message .= tr("Hello").",\r\n";
        $message .= tr("This email is sent in relation to your forgot password request at") . " - ".SITE_URL . "\r\n";
        $message .= tr("Your new password is").": ".$random_pass."\r\n";
        $message .= "\r\n";
        $message .= tr("Sincerely").",\r\n";
        $message .= SITE_TITLE;
        $this->body = $message;

    }

}

?>