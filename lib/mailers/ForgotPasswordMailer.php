<?php
include_once("mailers/Mailer.php");

class ForgotPasswordMailer extends Mailer
{

    /**
     * ForgotPasswordMailer constructor.
     * @param string $email Recipient email
     * @param string $random_pass the password
     * @param string|null $login_url url of the login page
     */
    public function __construct(string $email, string $random_pass, string $login_url="")
    {
        parent::__construct();

        $this->subject = tr("Forgot Password Request at: ") . SITE_TITLE;

        $this->to = $email;

        $message = tr("Hello") . ",\r\n";
        $message .= "\r\n";
        $message .= tr("This email is sent in relation to your forgot password request at") . " - ".SITE_TITLE . "\r\n";
        $message .= "\r\n";
        $message .= tr("Your new password is").": ".$random_pass."\r\n";
        $message .= "\r\n";
        if ($login_url) {
            $message .= tr("Use the following link to log into your account").": "."\r\n";
            $message .= "<a href='$login_url'>$login_url</a>\r\n";
            $message .= "\r\n";
        }
        $message .= tr("Sincerely").",\r\n";
        $message .= SITE_TITLE."\r\n";
        $message .= "\r\n";
        $message .= "<a href='".SITE_URL."'>".SITE_URL."</a>";

        $this->body = $this->templateMessage($message);

    }

}

?>
