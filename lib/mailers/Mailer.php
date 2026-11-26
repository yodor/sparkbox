<?php
include_once("mailers/MailView.php");

/**
 * Base of all mails. A subclass sets $to, $subject and the message: useTemplate() - a mail template of the layers
 * (<layer>/mails/<name>.php, MailView) in the frame of the mails - or $body, a message HTML the frame wraps once
 * ("\r\n" become line breaks).
 *
 * Sender: Config::DEFAULT_SERVICE_EMAIL / DEFAULT_SERVICE_NAME.
 * Transport: PHPMailer over authenticated SMTP when Config::PHPMAILER_ENABLED (the SMTP login is the sender,
 * config/mailer.php holds host, port and password), otherwise PHP mail().
 * A failed send returns false and is written to the PHP error log (always, not only with debug logging).
 */
abstract class Mailer
{

    protected string $to = "";
    protected string $from_name = ""; //senders name
    protected string $from_email = ""; //senders e-mail address
    protected string $subject = "";

    protected string $body = "";

    /**
     * Optional Reply-To address and name - the address a reply from the recipient goes to
     * (ie the visitor who sent an enquiry)
     */
    protected string $reply_to = "";
    protected string $reply_to_name = "";

    public function __construct()
    {
        $this->from_name = Spark::Get(Config::DEFAULT_SERVICE_NAME); //senders name
        $this->from_email = Spark::Get(Config::DEFAULT_SERVICE_EMAIL); //senders e-mail address
    }

    /**
     * Set the Reply-To of this mail. An invalid address is ignored (logged).
     * @param string $email
     * @param string $name
     * @return void
     */
    public function setReplyTo(string $email, string $name = "") : void
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Debug::ErrorLog("Reply-To ignored - invalid e-mail address");
            return;
        }
        $this->reply_to = $email;
        $this->reply_to_name = Mailer::HeaderText($name);
    }

    /**
     * The message: the mail template '$name' of the layers with '$data' in the frame (MailView) - set the subject
     * first (the preview text of the frame)
     * @throws Exception
     */
    protected function useTemplate(string $name, array $data = array()) : void
    {
        $this->body = MailView::Render($name, $data, $this->subject);
    }

    /**
     * The recipient - a preview sends the mail to another address (MailPreviews, the admin Settings > Mails)
     */
    public function setRecipient(string $email) : void
    {
        $this->to = $email;
    }

    public function getRecipient() : string
    {
        return $this->to;
    }

    public function getSubject() : string
    {
        return $this->subject;
    }

    /**
     * The HTML document of the mail - what send() sends
     */
    public function document() : string
    {
        return $this->templateMessage($this->body);
    }

    public function send() : bool
    {
        if (!$this->to) throw new Exception("Recipient missing.");
        if (!$this->subject) throw new Exception("Subject missing.");
        if (!$this->body) throw new Exception("Body missing.");

        // Check if PHPMailer SMTP is explicitly enabled for this specific host environment
        if (Spark::GetBoolean(Config::PHPMAILER_ENABLED)) {
            $result = $this->sendWithPHPMailer();
        }
        else {
            // Fallback legacy native mail function
            $result = $this->sendWithNativeMail();
        }

        if (!$result) {
            error_log(get_class($this) . ": sending to '{$this->to}' failed (subject: '{$this->subject}')");
        }
        return $result;
    }

    /**
     * Sends the email using authenticated SMTP (Ideal for Hostinger)
     */
    protected function sendWithPHPMailer() : bool
    {
        Debug::ErrorLog("Using PHPMailer");

        include_once ("mailers/PHPMailer/Exception.php");
        include_once ("mailers/PHPMailer/PHPMailer.php");
        include_once ("mailers/PHPMailer/SMTP.php");

        $mail = null;

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
            if ($this->reply_to) {
                $mail->addReplyTo($this->reply_to, $this->reply_to_name);
            }
            $mail->isHTML(true);
            $mail->Subject = $this->subject;
            $mail->Body    = $this->templateMessage($this->body);
            $mail->AltBody = Mailer::PlainText($mail->Body);

            $result = $mail->send();
            Debug::ErrorLog("PHPMailer Send Finished with result: " . $result. " | ErrorInfo: " . $mail->ErrorInfo);
            return $result;
        } catch (Exception $e) {
            error_log(get_class($this) . ": PHPMailer error: " . ($mail ? $mail->ErrorInfo : "") . " | " . $e->getMessage());
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
        return mb_send_mail($this->to, Mailer::HeaderText($this->subject), $templatedBody, $headers);
    }

    protected function processHeaders() : string
    {
        $headers = "From: " . $this->addressHeader($this->from_email, $this->from_name) . "\r\n";
        if ($this->reply_to) {
            $headers .= "Reply-To: " . $this->addressHeader($this->reply_to, $this->reply_to_name) . "\r\n";
        }

        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "Content-Disposition: inline\r\n";

        return $headers;

    }

    /**
     * 'name <email>' for a header, the name MIME encoded
     */
    protected function addressHeader(string $email, string $name) : string
    {
        $name = Mailer::HeaderText($name);
        if ($name === "") return "<" . $email . ">";
        return mb_encode_mimeheader($name, "UTF-8") . " <" . $email . ">";
    }

    /**
     * The message HTML in the frame of the mails (MailView), "\r\n" as line breaks.
     * A message that is already a document (a template - starts with <!DOCTYPE or <html>) is returned unchanged.
     * @param string $message
     * @return string
     */
    protected function templateMessage(string $message) : string
    {
        $start = strtolower(substr(ltrim($message), 0, 9));
        if (str_starts_with($start, "<!doctype") || str_starts_with($start, "<html")) return $message;
        return MailView::Frame(str_replace("\r\n", "<BR>", $message), $this->subject);
    }

    /**
     * Text for a header value: no line breaks (header injection), trimmed
     * @param string $text
     * @return string
     */
    public static function HeaderText(string $text) : string
    {
        return trim(str_replace(array("\r", "\n"), " ", $text));
    }

    /**
     * Plain text version of a mail HTML (the AltBody): line breaks kept, links as 'text (url)', tags removed
     * @param string $html
     * @return string
     */
    public static function PlainText(string $html) : string
    {
        //only the body, without the hidden preview text of the frame
        if (preg_match("/<body[^>]*>(.*)<\/body>/is", $html, $match)) $html = $match[1];
        $html = preg_replace("/<div style=\"display:none[^>]*>.*?<\/div>/is", "", $html);

        //links: 'text (url)' - only the url when the text is the url
        $html = preg_replace_callback("/<a\s[^>]*href=['\"]([^'\"]*)['\"][^>]*>(.*?)<\/a>/is", function (array $m) : string {
            $text = trim(strip_tags($m[2]));
            //a linked image (the logo): its alt text
            if ($text === "" && preg_match("/<img\s[^>]*alt=['\"]([^'\"]*)['\"]/i", $m[2], $alt)) $text = trim($alt[1]);
            $url = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, "UTF-8");
            if ($text === "" || strcmp(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, "UTF-8"), $url) === 0) return $url;
            return $text . " (" . $url . ")";
        }, $html);

        $html = preg_replace("/<br\s*\/?>/i", "\n", $html);
        $html = preg_replace("/<\/(p|div|tr|h[1-6]|li|table)>/i", "\n", $html);
        $html = preg_replace("/<\/t[dh]>/i", "\t", $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, "UTF-8");
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }
}
