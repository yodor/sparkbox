<?php
include_once("responders/json/JSONFormResponder.php");
include_once("forms/ContactRequestForm.php");
include_once("mailers/ContactRequestMailer.php");
include_once("objects/data/GTMConversionCommand.php");
include_once("objects/data/GTMCommand.php");
include_once("objects/data/GTMConvParam.php");
include_once("utils/script/Marketing.php");

/**
 * Contact enquiry in a JSONFormDialog - passed through to the owner by e-mail, nothing is stored.
 * Sent from DEFAULT_SERVICE_EMAIL to recipient() - DEFAULT_SERVICE_EMAIL here, the store and the sites override it
 * with their owner's address; Reply-To the visitor's e-mail, so the owner answers with Reply.
 * A failed send keeps the dialog open with an error, so the enquiry is not lost without notice.
 *
 * Page: new ContactRequestFormResponder(); JS: new JSONFormDialog() with setResponder("ContactRequestFormResponder")
 * Enquiries about one item (product): setAbout() - linked in the mail with its details.
 */
class ContactRequestFormResponder extends JSONFormResponder
{
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
     * The Google Ads conversion of the enquiry (Settings > Marketing) - sent to the page after the enquiry was sent
     */
    protected GTMConvParam $conversionParam = GTMConvParam::CONTACT_REQUEST;

    /**
     * @return GTMConversionCommand|null the conversion of conversionParam, null when it is not set
     */
    protected function conversion() : ?GTMConversionCommand
    {
        return Marketing::Conversion($this->conversionParam);
    }

    /**
     * The GA4 'generate_lead' of a sent enquiry - lead_type tells the enquiries apart (contact, product_query ...)
     */
    protected string $leadType = "contact";

    protected function leadEvent() : GTMCommand
    {
        $event = new GTMCommand(GTMCommand::COMMAND_EVENT);
        $event->setType("generate_lead");
        $event->addParameter("lead_type", $this->leadType);
        return $event;
    }

    protected function onProcessSuccess(JSONResponse $resp): void
    {
        parent::onProcessSuccess($resp);

        //label => value for the mail, in form order
        $values = array();
        foreach ($this->form->inputNames() as $name) {
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

        //GA4 generate_lead, and the Ads conversion when the site has one
        $resp->gtm = array_values(array_filter(array($this->leadEvent(), $this->conversion())));
    }
}
