<?php
/**
 * Mailer: the HTML document once, Reply-To, plain text part, header values without line breaks,
 * a failed send returns false and is logged
 */
include_once("mailers/Mailer.php");

class TestMailer extends Mailer
{
    public function __construct(string $body = "Hello\r\n<a href='https://example.com/x?a=1&amp;b=2'>the link</a>")
    {
        parent::__construct();
        $this->to = "client@example.com";
        $this->subject = "Test subject";
        $this->body = $body;
    }
    public function document() : string
    {
        return $this->templateMessage($this->templateMessage($this->body));
    }
    public function headers() : string
    {
        return $this->processHeaders();
    }
}

test("the message is wrapped in the HTML document once", function () {
    $html = (new TestMailer())->document();
    assertSame(1, substr_count($html, "<html>"), "wrapped twice");
    assertTrue(str_contains($html, "Hello<BR><a href="), "line breaks");
});

test("headers: From, Reply-To with a MIME encoded name, no header injection", function () {
    Spark::Set(Config::DEFAULT_SERVICE_EMAIL, "info@example.com");
    Spark::Set(Config::DEFAULT_SERVICE_NAME, "Example");
    $mailer = new TestMailer();
    $mailer->setReplyTo("visitor@example.com", "Иван\r\nBcc: evil@example.com");
    $headers = $mailer->headers();
    assertTrue(str_contains($headers, "From: Example <info@example.com>\r\n"), $headers);
    assertTrue((bool)preg_match("/^Reply-To: =\?UTF-8\?B\?[^\r\n]+ <visitor@example\.com>\r$/m", $headers), $headers);
    assertTrue(!str_contains($headers, "\r\nBcc:"), "injected header");

    $plain = new TestMailer();
    $plain->setReplyTo("not an address");
    assertTrue(!str_contains($plain->headers(), "Reply-To"), "invalid Reply-To used");
});

test("plain text part: line breaks, links with their address, entities decoded", function () {
    $text = Mailer::PlainText((new TestMailer())->document());
    assertSame("Hello\nthe link (https://example.com/x?a=1&b=2)", $text);
    $text = Mailer::PlainText("<html><body>a<br>b<a href='https://e.com/'>https://e.com/</a><table><tr><td>x</td><td>y</td></tr></table></body></html>");
    assertSame("a\nbhttps://e.com/x\ty", $text);
});

test("a failed send returns false and is logged", function () {
    Spark::Set(Config::PHPMAILER_ENABLED, true);
    Spark::Set(Config::PHPMAILER_SMTP_HOST, "127.0.0.1");
    Spark::Set(Config::PHPMAILER_SMTP_PORT, 1);
    Spark::Set(Config::PHPMAILER_SMTP_PASSWORD, "x");
    $log = TestCachePath() . "/mailer-error.log";
    @unlink($log);
    $previous = ini_set("error_log", $log);
    $result = (new TestMailer())->send();
    ini_set("error_log", $previous);
    Spark::Set(Config::PHPMAILER_ENABLED, false);
    assertSame(false, $result);
    $logged = (string)@file_get_contents($log);
    assertTrue(str_contains($logged, "TestMailer: PHPMailer error"), "PHPMailer error not logged: " . $logged);
    assertTrue(str_contains($logged, "TestMailer: sending to 'client@example.com' failed"), "failure not logged");
});
