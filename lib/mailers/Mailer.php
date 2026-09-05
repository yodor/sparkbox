<?php

abstract class Mailer
{

    protected string $to = "";
    protected string $from_name = ""; //senders name
    protected string $from_email = ""; //senders e-mail address
    protected string $subject = "";

    protected string $body = "";

    public function __construct()
    {
        $this->from_name = Spark::Get(Config::DEFAULT_SERVICE_NAME); //senders name
        $this->from_email = Spark::Get(Config::DEFAULT_SERVICE_EMAIL); //senders e-mail address
    }

    public function send() : bool
    {
        if (!$this->to) throw new Exception("Recipient missing.");
        if (!$this->subject) throw new Exception("Subject missing.");
        if (!$this->body) throw new Exception("Body missing.");

        // Check if PHPMailer SMTP is explicitly enabled for this specific host environment
        if (Spark::GetBoolean(Config::PHPMAILER_ENABLED)) {
            return $this->sendWithPHPMailer();
        }

        // Fallback legacy native mail function
        return $this->sendWithNativeMail();
    }

    /**
     * Sends the email using authenticated SMTP (Ideal for Hostinger)
     */
    protected function sendWithPHPMailer() : bool
    {
        Debug::ErrorLog("Using PHPMailer");

        // Adjust these paths to where you uploaded the PHPMailer src files
        include_once ("mailers/PHPMailer/Exception.php");
        include_once ("mailers/PHPMailer/PHPMailer.php");
        include_once ("mailers/PHPMailer/SMTP.php");



        try {

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP Configurations
            $mail->isSMTP();
            $mail->Host = Spark::Get(Config::PHPMAILER_SMTP_HOST);
            Debug::ErrorLog("PHPMailer HOST: " . $mail->Host);

            $mail->SMTPAuth   = true;
            $mail->Username   = $this->from_email;
            $mail->Password   = Spark::Get(Config::PHPMAILER_SMTP_PASSWORD);

            $port = Spark::GetInteger(Config::PHPMAILER_SMTP_PORT);
            $mail->Port = $port;
            Debug::ErrorLog("PHPMailer PORT: " . $mail->Port);

            // Automatically map the correct encryption type based on the port provided
            if ($port === 465) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                Debug::ErrorLog("PHPMailer ENCRYPTION_SMTPS");
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                Debug::ErrorLog("PHPMailer ENCRYPTION_STARTTLS");
            }

            $mail->CharSet    = 'UTF-8';

            // Recipients & Core Content
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($this->to);
            $mail->isHTML(true);
            $mail->Subject = $this->subject;
            $mail->Body    = $this->templateMessage($this->body);

            $result = $mail->send();
            Debug::ErrorLog("PHPMailer Send Finished with result: " . $result. " | ErrorInfo: " . $mail->ErrorInfo);
            return $result;
        } catch (Exception $e) {
            Debug::ErrorLog("PHPMailer Exception: " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Sends the email using raw native mail configuration
     */
    protected function sendWithNativeMail() : bool
    {
        $headers = $this->processHeaders();

        $templatedBody = $this->templateMessage($this->body);
        return mb_send_mail($this->to, $this->subject, $templatedBody, $headers);
    }

    protected function processHeaders() : string
    {

        $headers = "From: " . $this->from_name . " <" . $this->from_email . ">\r\n"; //optional headerfields

        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "Content-Disposition: inline\r\n";

        return $headers;

    }

    protected function templateMessage($message) : string
    {
        $str = "<html>";
        $str .= "<head>";
        $str .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
        $str .= "</head>";
        $str .= "<body>" . $message . "</body>";
        $str .= "</html>";
        $str = str_replace("\r\n", "<BR>", $str);
        return $str;
    }
}