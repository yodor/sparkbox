<?php
include_once("mailers/Mailer.php");
include_once("utils/url/URL.php");

/**
 * Passes a visitor's enquiry to the owner: the labelled form values, optionally what it is about (a product
 * with a link and a few details), Reply-To the visitor - the mail template mails/contact_request.php (it escapes
 * the visitor's text).
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
        $this->subject = tr("Enquiry from %s", Spark::Get(Config::SITE_DOMAIN));
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
        $this->useTemplate("contact_request", array(
            "values" => $this->values,
            "about_title" => $this->aboutTitle,
            "about_url" => ($this->aboutURL instanceof URL) ? (string)$this->aboutURL->fullURL() : "",
            "about_details" => $this->aboutDetails,
            "reply" => ($this->reply_to !== ""),
        ));
    }
}
