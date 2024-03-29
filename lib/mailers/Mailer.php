<?php

abstract class Mailer
{

    protected $to = "";
    protected $from_name = DEFAULT_SERVICE_NAME; //senders name
    protected $from_email = DEFAULT_SERVICE_EMAIL; //senders e-mail address
    protected $subject = "";

    protected $body = "";

    public function __construct()
    {
    }

    public function send()
    {

        $headers = $this->processHeaders();
        if (!$this->to) throw new Exception("Recipient missing.");
        if (!$this->subject) throw new Exception("Subject missing.");
        if (!$this->body) throw new Exception("Body missing.");

        return mb_send_mail($this->to, $this->subject, $this->body, $headers);
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

?>