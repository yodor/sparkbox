<?php
include_once("mailers/Mailer.php");
include_once("utils/url/URL.php");

/**
 * Passes a visitor's enquiry to the owner: the labelled form values, optionally what it is about (a product
 * with a link and a few details), Reply-To the visitor. Visitor text is escaped.
 */
class ContactRequestMailer extends Mailer
{
    /**
     * Labelled values in form order - label => value
     * @var array<string, string>
     */
    protected array $values = array();

    protected string $aboutTitle = "";
    protected ?URL $aboutURL = null;

    /**
     * Details of what the enquiry is about - label => value
     * @var array<string, string>
     */
    protected array $aboutDetails = array();

    public function __construct(string $recipient)
    {
        parent::__construct();
        $this->to = $recipient;
        $this->subject = "Запитване от сайта " . Spark::Get(Config::SITE_DOMAIN);
    }

    public function setSubject(string $subject) : void
    {
        $this->subject = $subject;
    }

    /**
     * @param array<string, string> $values label => value, as entered by the visitor
     * @return void
     */
    public function setValues(array $values) : void
    {
        $this->values = $values;
    }

    /**
     * What the enquiry is about: linked title and a few label => value details (code, price ...)
     * @param string $title
     * @param URL $url
     * @param array<string, string> $details
     * @return void
     */
    public function setAbout(string $title, URL $url, array $details = array()) : void
    {
        $this->aboutTitle = $title;
        $this->aboutURL = $url;
        $this->aboutDetails = $details;
    }

    /**
     * Build the body - call after setReplyTo()
     * @return void
     */
    public function prepareMessage() : void
    {
        $e = fn(string $text) => Spark::AttributeValue($text);

        $message = "";
        foreach ($this->values as $label => $value) {
            if (trim($value) === "") continue;
            $message .= "<b>" . $e($label) . ":</b> " . nl2br($e($value)) . "<br>";
        }

        if ($this->aboutURL instanceof URL) {
            $href = $e($this->aboutURL->fullURL()->toString());
            $message .= "<br><b>" . $e(tr("Относно")) . ":</b> <a href='$href'>" . $e($this->aboutTitle) . "</a><br>";
            foreach ($this->aboutDetails as $label => $value) {
                $message .= $e($label) . ": " . $e($value) . "<br>";
            }
        }

        $message .= "<br>";
        //Reply-To is set when the visitor gave an e-mail
        if ($this->reply_to) {
            $message .= $e(tr("Отговорете на това писмо, за да пишете на клиента.")) . "<br>";
        }
        $message .= $e(Spark::Get(Config::SITE_DOMAIN));

        $this->body = $message;
    }
}
