<?php
include_once("responders/json/JSONFormResponder.php");
include_once("forms/ContactRequestForm.php");
include_once("mailers/ContactRequestMailer.php");
include_once("objects/data/GTMConversionCommand.php");

/**
 * Contact enquiry in a JSONFormDialog - passed through to the owner by e-mail, nothing is stored.
 * Sent from DEFAULT_SERVICE_EMAIL to recipient() - DEFAULT_SERVICE_EMAIL here, the store and the sites override it
 * with their owner's address; Reply-To the visitor's e-mail, so the owner answers with Reply.
 * A failed send keeps the dialog open with an error, so the enquiry is not lost without notice.
 * Spam trap: input 'website', hidden by CSS - a filled value is answered as accepted and not sent.
 *
 * Page: new ContactRequestFormResponder(); JS: new JSONFormDialog() with setResponder("ContactRequestFormResponder")
 * Enquiries about one item (product): setAbout() - linked in the mail with its details.
 */
class ContactRequestFormResponder extends JSONFormResponder
{
    const string SPAM_TRAP = "website";

    protected string $aboutTitle = "";
    protected ?URL $aboutURL = null;
    protected array $aboutDetails = array();

    /**
     * Shown to the visitor after the enquiry was sent
     * @var string
     */
    protected string $acceptedMessage = "Заявката Ви беше изпратена успешно";

    /**
     * Mail subject
     * @var string
     */
    protected string $mailSubject = "";

    public function __construct()
    {
        parent::__construct();

        //spam trap - people do not see it (JSONFormDialog.css), bots fill every field
        $trap = DataInputFactory::Create(InputType::TEXT, ContactRequestFormResponder::SPAM_TRAP, "Website", 0);
        $trap->getRenderer()->input()?->setAttribute("autocomplete", "off");
        $trap->getRenderer()->input()?->setAttribute("tabindex", "-1");
        $this->form->addInput($trap);
    }

    protected function createForm(): InputForm
    {
        return new ContactRequestForm();
    }

    /**
     * What the enquiry is about - linked in the mail with its details
     * @param string $title
     * @param URL $url
     * @param array<string, string> $details label => value
     * @return void
     */
    public function setAbout(string $title, URL $url, array $details = array()) : void
    {
        $this->aboutTitle = $title;
        $this->aboutURL = $url;
        $this->aboutDetails = $details;
    }

    /**
     * Who receives the enquiry - the site's own mailbox here (a forwarder on the mail host can pass it on)
     * @return string
     */
    protected function recipient() : string
    {
        return (string)Spark::Get(Config::DEFAULT_SERVICE_EMAIL);
    }

    /**
     * The mail of the enquiry, to recipient()
     * @return ContactRequestMailer
     */
    protected function createMailer() : ContactRequestMailer
    {
        return new ContactRequestMailer($this->recipient());
    }

    /**
     * Marketing conversion sent to the page after the enquiry was sent - none here (the store reads its marketing config)
     * @return GTMConversionCommand|null
     */
    protected function conversion() : ?GTMConversionCommand
    {
        return null;
    }

    protected function onProcessSuccess(JSONResponse $resp): void
    {
        parent::onProcessSuccess($resp);

        if (trim((string)$this->form->getInput(ContactRequestFormResponder::SPAM_TRAP)->getValue()) !== "") {
            Debug::ErrorLog("Contact enquiry dropped - spam trap filled");
            $resp->message = tr($this->acceptedMessage);
            return;
        }

        //label => value for the mail, in form order
        $values = array();
        foreach ($this->form->inputNames() as $name) {
            if ($name === ContactRequestFormResponder::SPAM_TRAP) continue;
            $input = $this->form->getInput($name);
            $values[tr($input->getLabel())] = (string)$input->getValue();
        }

        $mailer = $this->createMailer();
        if ($this->mailSubject) $mailer->setSubject($this->mailSubject);
        $mailer->setValues($values);
        if ($this->form->haveInput("email")) {
            $name = $this->form->haveInput("fullname") ? (string)$this->form->getInput("fullname")->getValue() : "";
            $mailer->setReplyTo((string)$this->form->getInput("email")->getValue(), $name);
        }
        if ($this->aboutURL instanceof URL) {
            $mailer->setAbout($this->aboutTitle, $this->aboutURL, $this->aboutDetails);
        }
        $mailer->prepareMessage();

        if (!$mailer->send()) {
            //nothing is stored - the visitor must know the enquiry did not go out
            Debug::ErrorLog("Contact enquiry could not be sent to: " . $this->recipient());
            throw new Exception(tr("Изпращането не беше успешно. Моля, опитайте отново по-късно или ни се обадете."));
        }

        $resp->message = tr($this->acceptedMessage);

        $conversion = $this->conversion();
        if ($conversion) {
            $resp->gtm = $conversion;
        }
    }
}
