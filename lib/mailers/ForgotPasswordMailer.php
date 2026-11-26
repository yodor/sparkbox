<?php
include_once("mailers/Mailer.php");

/**
 * The new password of an administrator (the admin 'forgot password') - the mail template mails/forgot_password.php
 */
class ForgotPasswordMailer extends Mailer
{

    /**
     * ForgotPasswordMailer constructor.
     * @param string $email Recipient email
     * @param string $random_pass the password
     * @param string $login_url url of the login page
     */
    public function __construct(string $email, string $random_pass, ?URL $login_url=null)
    {
        parent::__construct();

        $this->subject = tr("Forgot Password Request at: ") . Spark::Get(Config::SITE_TITLE);

        $this->to = $email;

        $this->useTemplate("forgot_password", array(
            "password" => $random_pass,
            "login" => $login_url ? (string)$login_url->fullURL() : "",
        ));

    }

}