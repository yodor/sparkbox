<?php
include_once("utils/language.php");

/**
 * The HTML of a mail: a template of the layers inside the mail frame.
 *
 *   <layer>/mails/<name>.php   the content of one mail - core lib/mails, store lib/store/mails, the site class/mails;
 *                              the most specific layer wins (a site rewords or restyles a mail with its own copy)
 *   <layer>/mails/frame.php    the frame of every mail: the logo, the content, the footer - a site may have its own
 *
 * A template runs as a method of the view: $this->data are the values of the mailer, $this->e() escapes,
 * $this->text() / $this->button() / $this->link() write the parts with their inline styles (mail clients ignore
 * <style> blocks). The texts are English keys of the language packs: tr("Hello").
 *
 * The logo (an absolute address - mail clients load it from the site; SVG is not shown by Gmail / Outlook):
 *   the logo uploaded in the admin (the store: Content > Logo, the /logo address), else the first site file of
 *   images/logo_mail.png, images/logo_mail.jpg, images/logo.png, images/logo.jpg - else the title of the site as text.
 * The colours of the frame: Config::MAIL_ACCENT_COLOR (the line above the logo, buttons, links),
 * Config::MAIL_HEADER_BACKGROUND (the logo band - a logo made for a dark header).
 */
class MailView
{
    const string FOLDER = "mails";
    const string FRAME = "frame";

    /**
     * The site logo files a mail may use, in this order (below the site root)
     */
    const array LOGO_FILES = array("images/logo_mail.png", "images/logo_mail.jpg", "images/logo.png", "images/logo.jpg");

    /**
     * The values of the mail (the mailer sets them)
     * @var array<string, mixed>
     */
    public array $data = array();

    /**
     * The title of the mail (its subject) - the frame shows it as the hidden preview text
     */
    public string $title = "";

    /**
     * The content HTML (the frame writes it)
     */
    public string $content = "";

    public function __construct(array $data = array(), string $title = "")
    {
        $this->data = $data;
        $this->title = $title;
    }

    /**
     * The mail '$name' in the frame - a full HTML document
     * @throws Exception the template of the layers is not found
     */
    public static function Render(string $name, array $data = array(), string $title = "") : string
    {
        $view = new MailView($data, $title);
        return MailView::Frame($view->template($name), $title);
    }

    /**
     * '$content' (HTML) in the frame of the mails - a full HTML document
     */
    public static function Frame(string $content, string $title = "") : string
    {
        $view = new MailView(array(), $title);
        $view->content = $content;
        return $view->template(MailView::FRAME);
    }

    /**
     * The output of the template '$name' of the layers
     * @throws Exception
     */
    protected function template(string $name) : string
    {
        $file = SparkLoader::Factory(MailView::FOLDER)->find($name);
        if ($file === "") throw new Exception("Mail template not found: " . MailView::FOLDER . "/$name.php");
        ob_start();
        try {
            include($file);
        }
        finally {
            $html = (string)ob_get_clean();
        }
        return $html;
    }

    /**
     * The output of another template of the layers with the values of this mail - a part several mails share
     * (the order summary)
     * @throws Exception
     */
    public function part(string $name) : string
    {
        return $this->template($name);
    }

    /**
     * A value of the mail ('' when not set)
     */
    public function get(string $key) : mixed
    {
        return $this->data[$key] ?? "";
    }

    /**
     * '$value' as HTML text
     */
    public function e(mixed $value) : string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, "UTF-8");
    }

    public function accent() : string
    {
        return MailView::Color((string)Spark::Get(Config::MAIL_ACCENT_COLOR), "#333333");
    }

    /**
     * The background of the logo band
     */
    public function headerBackground() : string
    {
        return MailView::Color((string)Spark::Get(Config::MAIL_HEADER_BACKGROUND), "#ffffff");
    }

    /**
     * '$color' when it is a hex colour, else '$default' (the value goes into a style attribute)
     */
    protected static function Color(string $color, string $default) : string
    {
        $color = trim($color);
        return preg_match("/^#[0-9a-fA-F]{3,8}$/", $color) ? $color : $default;
    }

    /**
     * A paragraph of the text '$text' (escaped; line breaks kept)
     */
    public function text(string $text) : string
    {
        return $this->paragraph(str_replace("\n", "<br>", $this->e($text)));
    }

    /**
     * A paragraph of '$html' (already HTML)
     */
    public function paragraph(string $html) : string
    {
        return "<p style='margin:0 0 16px 0;'>" . $html . "</p>\n";
    }

    /**
     * A table of label => value rows - the values are HTML (escape the texts with e()); empty values are left out
     * @param array<string, string> $rows
     */
    public function details(array $rows) : string
    {
        $html = "";
        foreach ($rows as $label => $value) {
            if (trim((string)$value) === "") continue;
            $html .= "<tr><td style='padding:4px 12px 4px 0;color:#6b7280;vertical-align:top;white-space:nowrap;'>" . $this->e($label) . ":</td>"
                . "<td style='padding:4px 0;vertical-align:top;'>" . $value . "</td></tr>\n";
        }
        if ($html === "") return "";
        return "<table role='presentation' cellpadding='0' cellspacing='0' border='0' style='margin:0 0 16px 0;'>\n" . $html . "</table>\n";
    }

    /**
     * A heading of a part of the mail
     */
    public function heading(string $text) : string
    {
        return "<h2 style='margin:24px 0 8px 0;font-size:17px;color:#222222;'>" . $this->e($text) . "</h2>\n";
    }

    /**
     * A link to '$url' - its text '$text', the address itself without one
     */
    public function link(string $url, string $text = "") : string
    {
        $text = ($text === "") ? $url : $text;
        return "<a href='" . $this->e($url) . "' style='color:" . $this->accent() . ";'>" . $this->e($text) . "</a>";
    }

    /**
     * A button to '$url' (a table - the buttons of mail clients)
     */
    public function button(string $url, string $text) : string
    {
        $accent = $this->accent();
        return "<table role='presentation' cellpadding='0' cellspacing='0' border='0' style='margin:8px 0 24px 0;'><tr>"
            . "<td style='background:$accent;border-radius:4px;'>"
            . "<a href='" . $this->e($url) . "' style='display:inline-block;padding:12px 24px;color:#ffffff;"
            . "text-decoration:none;font-weight:bold;'>" . $this->e($text) . "</a>"
            . "</td></tr></table>\n";
    }

    /**
     * The title of the site
     */
    public function siteTitle() : string
    {
        return (string)Spark::Get(Config::SITE_TITLE);
    }

    /**
     * The address of the site (its root)
     */
    public function siteURL() : string
    {
        return rtrim((string)Spark::Get(Config::SITE_URL), "/") . "/";
    }

    /**
     * The address of the logo of the mails - '' when the site has none a mail can show
     */
    public function logoURL() : string
    {
        return $this->logo()[0];
    }

    /**
     * The width the logo is shown at: its own width, 200px at most (a logo of 400px - sharp on HiDPI screens)
     */
    public function logoWidth() : int
    {
        $file = $this->logo()[1];
        $info = ($file !== "") ? @getimagesize($file) : false;
        return is_array($info) ? min(200, (int)$info[0]) : 200;
    }

    /**
     * array(the address, the file) of the logo - array("", "") without one
     * @return array{0: string, 1: string}
     */
    protected function logo() : array
    {
        //the logo uploaded in the admin (the store /logo address)
        $cache = (string)Spark::Get(Config::CACHE_PATH);
        if ($cache !== "" && is_file($cache . "/logo") && MailView::IsRaster($cache . "/logo")) return array($this->siteURL() . "logo", $cache . "/logo");

        $root = (string)Spark::Get(Config::APP_PATH);
        foreach (MailView::LOGO_FILES as $file) {
            $path = $root . "/" . $file;
            if (is_file($path)) return array($this->siteURL() . $file . "?v=" . base_convert((string)filemtime($path), 10, 36), $path);
        }
        return array("", "");
    }

    /**
     * A PNG / JPEG / GIF file (mail clients show these)
     */
    protected static function IsRaster(string $file) : bool
    {
        $info = @getimagesize($file);
        return is_array($info) && in_array($info[2], array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF), true);
    }
}
