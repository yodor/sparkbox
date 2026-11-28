<?php
include_once("templates/TemplateContent.php");
include_once("mailers/MailPreviews.php");
include_once("components/Container.php");
include_once("components/TextComponent.php");
include_once("pages/SparkPage.php");
include_once("auth/AuthContext.php");
include_once("utils/url/URL.php");
include_once("utils/url/URLParameter.php");
include_once("beans/AdminUsersBean.php");
include_once("objects/OrderColumn.php");

/**
 * Settings > Mails: every mail of the layers (MailPreviews) as it is sent - the subject, the HTML in a frame, the
 * plain text part - and a test send to an administrator of the site (the one logged in by default; a shop owner who
 * is an administrator sees the mails in his own mailbox). Only the addresses of the active administrators.
 */
class MailPreviewsContent extends TemplateContent
{
    const string KEY_MAIL = "mail";
    const string KEY_SEND = "send_test";
    const string KEY_RECIPIENT = "recipient";

    /**
     * @var array<string, array{0: string, 1: Closure}>
     */
    protected array $mails = array();

    protected string $selected = "";

    public function __construct()
    {
        parent::__construct();
        $this->mails = MailPreviews::All();
        $selected = (string)($_GET[MailPreviewsContent::KEY_MAIL] ?? "");
        $this->selected = isset($this->mails[$selected]) ? $selected : (string)(array_key_first($this->mails) ?? "");
    }

    protected function getContentTitle(): string
    {
        return "Mails";
    }

    /**
     * The e-mail of the administrator logged in - the test sends go there
     */
    protected function adminEmail() : string
    {
        $context = Module::Active()->getAuthContext();
        if (is_null($context)) return "";
        $email = trim((string)$context->getData()->get(AuthContext::EMAIL));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : "";
    }

    /**
     * The active administrators with an e-mail address: e-mail => their name and e-mail (the choices of the test send)
     * @return array<string, string>
     */
    protected function adminRecipients() : array
    {
        $recipients = array();
        $admins = new AdminUsersBean();
        $query = $admins->query($admins->key(), "email", "fullname");
        $query->stmt->where()->expression("(suspend IS NULL OR suspend = 0)");
        $query->stmt->order("fullname", OrderDirection::ASC);
        $query->exec();
        while ($row = $query->nextResult()) {
            $email = trim((string)$row->get("email"));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            $name = trim((string)$row->get("fullname"));
            $recipients[$email] = ($name !== "") ? "$name <$email>" : $email;
        }
        return $recipients;
    }

    public function processInput(): void
    {
        parent::processInput();
        if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST" || !isset($_POST[MailPreviewsContent::KEY_SEND])) return;

        $name = (string)$_POST[MailPreviewsContent::KEY_SEND];
        //the chosen administrator - only an address of an active administrator, else the one logged in
        $email = $this->adminEmail();
        $chosen = trim((string)($_POST[MailPreviewsContent::KEY_RECIPIENT] ?? ""));
        if ($chosen !== "" && isset($this->adminRecipients()[$chosen])) $email = $chosen;
        try {
            if ($email === "") throw new Exception(tr("Your administrator account has no e-mail address"));
            $mailer = MailPreviews::Create($name);
            $mailer->setRecipient($email);
            if (!$mailer->send()) throw new Exception(tr("The mail was not sent - see the error log"));
            Session::SetAlert(tr("The test mail was sent to %s", $email));
        }
        catch (Exception $e) {
            Session::SetAlert($e->getMessage());
        }
        header("Location: " . $this->mailURL($name)->toString());
        exit;
    }

    /**
     * The address of this page with the mail '$name'
     */
    protected function mailURL(string $name) : URL
    {
        $url = URL::Current();
        $url->remove(MailPreviewsContent::KEY_MAIL);
        $url->add(new URLParameter(MailPreviewsContent::KEY_MAIL, $name));
        return $url;
    }

    public function initialize(): void
    {
        $this->cmp = new Container(false);
        $this->cmp->setComponentClass("MailPreviews");

        //the list of the mails
        $list = new Container(false);
        $list->setComponentClass("list");
        foreach ($this->mails as $name => [$title]) {
            $url = $this->mailURL($name);
            $link = new TextComponent("", "item");
            $link->setTagName("a");
            $link->setAttribute("href", $url->toString());
            if ($name === $this->selected) $link->setAttribute("selected", "1");
            $link->setText($title);
            $list->items()->append($link);
        }
        $this->cmp->items()->append($list);

        $view = new Container(false);
        $view->setComponentClass("view");
        $this->cmp->items()->append($view);
        if ($this->selected === "") return;

        try {
            $mailer = MailPreviews::Create($this->selected);
            $html = $mailer->document();
            $e = fn(string $text) : string => Spark::AttributeValue($text);

            $email = $this->adminEmail();
            $recipients = $this->adminRecipients();
            if ($email !== "" && !isset($recipients[$email])) $recipients = array($email => $email) + $recipients;
            $options = "";
            foreach ($recipients as $address => $label) {
                $options .= "<option value='" . $e($address) . "'" . (($address === $email) ? " selected" : "") . ">" . $e($label) . "</option>";
            }
            $head = new TextComponent("", "head");
            $head->setTagName("div");
            $head->setContents("<div class='subject'><span class='label'>" . $e(tr("Subject")) . "</span> " . $e($mailer->getSubject()) . "</div>"
                . "<div class='recipient'><span class='label'>" . $e(tr("Recipient")) . "</span> " . $e($mailer->getRecipient()) . "</div>"
                . "<form method='post' class='test'><label>" . $e(tr("Send a test to")) . " <select name='" . MailPreviewsContent::KEY_RECIPIENT . "'"
                . ((count($recipients) < 1) ? " disabled" : "") . ">" . $options . "</select></label> "
                . "<button type='submit' class='ColorButton' name='" . MailPreviewsContent::KEY_SEND . "' value='" . $e($this->selected) . "'"
                . ((count($recipients) < 1) ? " disabled" : "") . ">" . $e(tr("Send")) . "</button></form>");
            $view->items()->append($head);

            //the mail in a sandboxed frame (no scripts, no forms) - srcdoc escaped fully (the entities of the mail too)
            $frame = new TextComponent("", "frame");
            $frame->setTagName("div");
            $frame->setContents("<iframe class='mail' sandbox title='" . $e($mailer->getSubject()) . "' srcdoc='"
                . htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, "UTF-8", true) . "'></iframe>");
            $view->items()->append($frame);

            $plain = new TextComponent("", "plain");
            $plain->setTagName("details");
            $plain->setContents("<summary>" . $e(tr("Plain text part")) . "</summary><pre>" . $e(Mailer::PlainText($html)) . "</pre>");
            $view->items()->append($plain);
        }
        catch (Exception $ex) {
            $error = new TextComponent("", "error");
            $error->setTagName("div");
            $error->setText($ex->getMessage());
            $view->items()->append($error);
        }
    }
}
