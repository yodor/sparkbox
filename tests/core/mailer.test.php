<?php
/**
 * Mailer: the HTML document once, Reply-To, plain text part, header values without line breaks,
 * a failed send returns false and is logged
 */
include_once("mailers/Mailer.php");

//the layers of the loader as at boot (the mail templates of the core)
SparkLoader::AddLocation(realpath(TESTS_PATH . "/../lib"), "");

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

test("the message is wrapped in the frame of the mails once", function () {
    $html = (new TestMailer())->document();
    assertSame(1, substr_count($html, "<html"), "wrapped twice");
    assertTrue(str_contains($html, "Hello<BR><a href="), "line breaks");
    assertTrue(str_contains($html, "max-width:600px"), "the frame");
});

class TemplateMailer extends Mailer
{
    public function __construct(array $data)
    {
        parent::__construct();
        $this->to = "client@example.com";
        $this->subject = "Reset <b>";
        $this->useTemplate("forgot_password", $data);
    }
    public function document() : string
    {
        return $this->templateMessage($this->body);
    }
}

test("a mail template of the layers in the frame: values escaped, the subject as the preview, plain text", function () {
    Spark::Set(Config::SITE_TITLE, "Test <Site>");
    Spark::Set(Config::MAIL_ACCENT_COLOR, "#123456");
    $html = (new TemplateMailer(array("password" => "<script>x</script>", "login" => "https://example.com/admin/login?a=1&b=2")))->document();
    assertSame(1, substr_count($html, "<html"), "one document");
    assertTrue(str_contains($html, "&lt;script&gt;x&lt;/script&gt;") && !str_contains($html, "<script>x"), "the value escaped");
    assertTrue(str_contains($html, "href='https://example.com/admin/login?a=1&amp;b=2'"), "the button address");
    assertTrue(str_contains($html, "Test &lt;Site&gt;"), "the site title escaped");
    assertTrue(str_contains($html, "background:#123456"), "the accent colour");
    assertTrue(str_contains($html, "<title>Reset &lt;b&gt;</title>"), "the subject");
    $text = Mailer::PlainText($html);
    assertTrue(str_starts_with($text, "Test <Site>"), $text);
    assertTrue(str_contains($text, "<script>x</script>") && str_contains($text, "(https://example.com/admin/login?a=1&b=2)"), $text);
    assertTrue(!str_contains($text, "Reset <b>"), "no hidden preview text in the plain text");
});

test("the colours of the frame are checked (a style attribute), a missing template is an error", function () {
    Spark::Set(Config::MAIL_ACCENT_COLOR, "red;background:url(x)");
    assertSame("#333333", (new MailView())->accent());
    assertThrows(fn() => MailView::Render("no_such_mail"), "Mail template not found");
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
    assertTrue(str_contains($text, "\n\nHello\nthe link (https://example.com/x?a=1&b=2)\n\n"), $text);
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
