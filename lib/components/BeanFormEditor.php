<?php
include_once("db/BeanTransactor.php");
include_once("beans/DBTableBean.php");
include_once("forms/InputForm.php");
include_once("forms/renderers/FormRenderer.php");
include_once("forms/processors/FormProcessor.php");


include_once("dialogs/json/BeanTranslationDialog.php");
include_once("objects/events/BeanFormEditorEvent.php");

class BeanFormEditor extends FormRenderer implements IBeanEditor
{

    protected array $messages = array();

    const int MESSAGE_ADD = 1;
    const int MESSAGE_UPDATE = 2;

    /**
     * @var DBTableBean
     */
    protected DBTableBean $bean;

    protected int $editID = -1;


    /**
     * @var FormProcessor
     */
    protected FormProcessor $processor;

    /**
     * @var BeanTransactor
     */
    protected BeanTransactor $transactor;

    protected string $error = "";


    /**
     * Allow external callers to set redirect url
     * @var URL|null
     */
    protected ?URL $redirect_url = null;

    /**
     * @var BeanTranslationDialog
     */
    protected BeanTranslationDialog $bean_translator;

    public function __construct(DBTableBean $bean, InputForm $form)
    {

        parent::__construct($form);
        $this->setClassName("BeanFormEditor");

        $this->setMessage(tr("Information was updated"), BeanFormEditor::MESSAGE_UPDATE);
        $this->setMessage(tr("Information was added"), BeanFormEditor::MESSAGE_ADD);

        $this->bean_translator = new BeanTranslationDialog();

        $this->processor = new FormProcessor();

        $this->bean = $bean;
        $this->transactor = new BeanTransactor($this->bean);

        $fieldNames = $this->form->inputNames();
        foreach ($fieldNames as $fieldName) {

            $field = $this->form->getInput($fieldName);
            $renderer = $field->getRenderer();

            if ($renderer instanceof MCETextArea) {

                $responder = $renderer->getImageBrowser()->getResponder();
                if ($responder instanceof MCEImageBrowserResponder) {
                    $responder->setSection(get_class($this->form), $fieldName);
                    $responder->setOwnerID(SparkAdminPage::Instance()->getUserID());
                }

            }

        }

        SparkEventManager::emit(new BeanFormEditorEvent(BeanFormEditorEvent::EDITOR_CREATED, $this));

    }

    public function processAttributes(): void
    {
        parent::processAttributes();
        $this->setAttribute("editID", $this->editID);
        $this->setAttribute("bean", get_class($this->bean));
        $this->setAttribute("form", get_class($this->form));
    }

    public function setRedirectURL(URL $url) : void
    {
        $this->redirect_url = $url;
    }

    public function getRedirectURL(): ?URL
    {
        return $this->redirect_url;
    }

    /**
     * Set the message text to show after successful add or update
     * @param string $message
     * @param int $type MESSAGE_ADD or MESSAGE_UPDATE
     */
    public function setMessage(string $message, int $type) : void
    {
        $this->messages[$type] = $message;
    }

    /**
     * Get message text that is shown after processInput
     * @param int $type
     * @return string
     */
    public function getMessage(int $type): string
    {
        return $this->messages[$type];
    }

    public function getEditID(): int
    {
        return $this->editID;
    }

    public function setEditID(int $editID): void
    {
        $this->editID = $editID;
        $this->transactor->setEditID($editID);
    }

    public function getBean(): DBTableBean
    {
        return $this->bean;
    }

    public function setBean(DBTableBean $bean): void
    {
        $this->bean = $bean;
        $this->transactor->setBean($bean);
        $this->form->setBean($bean);
    }

    public function getForm(): InputForm
    {
        return $this->form;
    }

    public function getProcessor(): IFormProcessor
    {
        return $this->processor;
    }

    public function setProcessor(IFormProcessor $processor) : void
    {
        $this->processor = $processor;
    }

    public function getTransactor(): BeanTransactor
    {
        return $this->transactor;
    }

    public function setTransactor(BeanTransactor $transactor) : void
    {
        $this->transactor = $transactor;
    }

    public function processInput() : void
    {

        try {

            $message = $this->getMessage(BeanFormEditor::MESSAGE_ADD);

            //will process external editID only if editID is not set already set
            $editID = $this->editID;
            if ($editID < 1 && isset($_GET["editID"])) {
                $editID = (int)$_GET["editID"];
                Debug::ErrorLog("Using editID from _GET");
            }

            //update form and transactor editIDs
            $this->setEditID($editID);

            if ($this->editID>0) {
                $message = $this->getMessage(BeanFormEditor::MESSAGE_UPDATE);

                Debug::ErrorLog("Loading bean data ".get_class($this->bean)." ID: ".$this->editID);
                //sets bean and editID to form
                $this->form->loadBeanData($this->getEditID(), $this->getBean());
                SparkEventManager::emit(new BeanFormEditorEvent(BeanFormEditorEvent::FORM_BEAN_LOADED, $this));

            }
            else {
                Debug::ErrorLog("Add mode - not loading bean data");
            }

            Debug::ErrorLog("Calling form processor");
            $this->processor->process($this->form);
            SparkEventManager::emit(new BeanFormEditorEvent(BeanFormEditorEvent::FORM_PROCESSED, $this));

            $process_status = $this->processor->getStatus();

            if ($process_status === IFormProcessor::STATUS_NOT_PROCESSED) {
                Debug::ErrorLog("FormProcessor - NOT_PROCESSED");
                return;
            }
            if ($process_status === IFormProcessor::STATUS_ERROR) {
                Debug::ErrorLog("FormProcessor - ERROR");
                throw new Exception($this->processor->getMessage());
            }

            //form is ok transact to db using the bean
            Debug::ErrorLog("Transactor ProcessForm");
            $this->transactor->processForm($this->form);
            SparkEventManager::emit(new BeanFormEditorEvent(BeanFormEditorEvent::FORM_VALUES_TRANSACTED, $this));

            Debug::ErrorLog("Transactor ProcessBean");
            $this->transactor->processBean();
            SparkEventManager::emit(new BeanFormEditorEvent(BeanFormEditorEvent::FORM_BEAN_TRANSACED, $this));

            $url = $this->redirect_url;
            //externally set URL for redirection
            if ($url instanceof URL) {

            }
            else {
                //add function - redirect to edit function
                if ($this->editID<1) {
                    $url = URL::Current();
                    $url->add(new URLParameter("editID", $this->editID));
                }
            }

            Session::SetAlert($message);
            if ($url instanceof URL) {
                Debug::ErrorLog("Redirecting to: " . $url);
                header("Location: " . $url);
                exit;
            }

        }
        catch (Exception $e) {
            Debug::ErrorLog("Exception received: " . $e->getMessage());
            Session::SetAlert($e->getMessage());
            $this->error = $e->getMessage();
        }

    }

}