<?php
include_once("components/Component.php");
include_once("beans/DBTableBean.php");
include_once("forms/InputForm.php");

include_once("forms/processors/FormProcessor.php");
include_once("forms/renderers/FormRenderer.php");
include_once("db/BeanTransactor.php");

include_once("responders/json/UploadControlResponder.php");

include_once("dialogs/BeanTranslationDialog.php");

include_once("objects/SparkObserver.php");


class BeanFormEditorEvent extends SparkEvent {
    const FORM_BEAN_LOADED = "FORM_BEAN_LOADED";
    const FORM_PROCESSED = "FORM_PROCESSED";
    const FORM_VALUES_TRANSACTED = "FORM_VALUES_TRANSACTED";
    const FORM_BEAN_TRANSACED = "FORM_BEAN_TRANSACED";
};


class BeanFormEditor extends Container implements IBeanEditor
{

    protected array $messages = array();

    const MESSAGE_ADD = 1;
    const MESSAGE_UPDATE = 2;

    /**
     * @var DBTableBean
     */
    protected DBTableBean $bean;

    protected int $editID = -1;

    /**
     * @var InputForm
     */
    protected InputForm $form;

    /**
     * @var FormRenderer
     */
    protected FormRenderer $form_render;

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
     * Redirect to this URL on successful processing
     * @var URLBuilder|null
     */
    protected ?URLBuilder $redirect_url = null;

    /**
     * Enable/Disable redirect logic during processInput
     * @var bool
     */
    protected bool $redirect_enabled = false;

    /**
     * @var BeanTranslationDialog
     */
    protected BeanTranslationDialog $bean_translator;

    public function __construct(DBTableBean $bean, InputForm $form)
    {

        parent::__construct();



        $this->redirect_enabled = true;

        $this->setMessage("Information was updated", BeanFormEditor::MESSAGE_UPDATE);
        $this->setMessage("Information was added", BeanFormEditor::MESSAGE_ADD);

        $this->bean = $bean;
        $this->form = $form;

        $this->form_render = new FormRenderer($form);

        $this->attributes["bean"] = get_class($this->bean);
        $this->form_render->setName(get_class($this->bean));

        $this->processor = new FormProcessor();

        $this->transactor = new BeanTransactor($this->bean, $this->editID);

        $fieldNames = $this->form->getInputNames();
        foreach ($fieldNames as $fieldName) {

            $field = $this->form->getInput($fieldName);
            $renderer = $field->getRenderer();

            if ($renderer instanceof MCETextArea) {

                $handler = $renderer->getImageBrowser()->getHandler();

                $handler->setSection(get_class($this->form), $fieldName);
                $handler->setOwnerID(SparkAdminPage::Instance()->getUserID());

            }

        }

        $this->bean_translator = new BeanTranslationDialog();

        $this->setEditID(-1);

        $this->append($this->form_render);

        $this->setObserver(new SparkObserver());

    }

    public function setRedirectEnabled(bool $mode)
    {
        $this->redirect_enabled = $mode;
    }

    public function isRedirectEnabled() : bool
    {
        return $this->redirect_enabled;
    }

    public function setRedirectURL(URLBuilder $url)
    {
        $this->redirect_url = $url;
    }

    public function getRedirectURL(): ?URLBuilder
    {
        return $this->redirect_url;
    }

    /**
     * Set the message to show after successful add or update
     * '$message' will be tr()'ed before setting it to Session::SetAlert
     * @param string $message
     * @param int $type MESSAGE_ADD or MESSAGE_UPDATE
     */
    public function setMessage(string $message, int $type)
    {
        $this->messages[$type] = $message;
    }

    /**
     * Use BeanFormEditor::MESSAGE_ADD or MESSAGE_UPDATE for type
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

    public function setEditID(int $editID)
    {
        $this->editID = $editID;
        $this->attributes["editID"] = $this->editID;
        $this->transactor->setEditID($editID);

    }

    public function getBean(): DBTableBean
    {
        return $this->bean;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
        $this->transactor->setBean($bean);
    }

    public function getForm(): InputForm
    {
        return $this->form;
    }

    public function getProcessor(): IFormProcessor
    {
        return $this->processor;
    }

    public function setProcessor(IFormProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function getTransactor(): BeanTransactor
    {
        return $this->transactor;
    }

    public function setTransactor(BeanTransactor $transactor)
    {
        $this->transactor = $transactor;
    }

    public function processInput()
    {

        //will process external editID only if editID is not set
        if ($this->editID < 1 && isset($_GET["editID"])) {

            $this->setEditID((int)$_GET["editID"]);
            debug("Using editID='$this->editID' from _GET ");

        }

        try {

            debug("Loading bean data into form");
            $this->form->loadBeanData($this->getEditID(), $this->getBean());
            $this->notify(new BeanFormEditorEvent(BeanFormEditorEvent::FORM_BEAN_LOADED, $this));
            debug("Calling form processor");
            $this->processor->process($this->form);
            $this->notify(new BeanFormEditorEvent(BeanFormEditorEvent::FORM_PROCESSED, $this));

            $process_status = $this->processor->getStatus();
            debug("FormProcessor status => " . $process_status);

            //form is ok transact to db using the bean
            if ($process_status === IFormProcessor::STATUS_OK) {

                debug("Transacting form values");
                $this->transactor->processForm($this->form);
                $this->notify(new BeanFormEditorEvent(BeanFormEditorEvent::FORM_VALUES_TRANSACTED, $this));

                debug("Processing bean");
                $this->transactor->processBean();
                debug("Process status is successful");
                $this->notify(new BeanFormEditorEvent(BeanFormEditorEvent::FORM_BEAN_TRANSACED, $this));

                $redirectURL = $this->redirect_url;

                //reload after adding item?
                if ($this->editID < 1) {

                    $msg = $this->getMessage(BeanFormEditor::MESSAGE_ADD);
                    if ($msg) Session::SetAlert(tr($msg));

                    if (!$redirectURL) {
                        debug("RedirectURL is not set - Setting redirectURL to the edit location");
                        $lastID = $this->transactor->getLastID();
                        $redirectURL = new URLBuilder();
                        $redirectURL->buildFrom(SparkPage::Instance()->getPageURL());
                        $redirectURL->add(new URLParameter("editID", $lastID));
                    }

                }
                else {

                    $msg = $this->getMessage(BeanFormEditor::MESSAGE_UPDATE);
                    if ($msg) Session::SetAlert(tr($msg));

                    if (!$redirectURL) {
                        debug("RedirectURL is not set - Setting redirectURL to the current location");
                        $redirectURL = new URLBuilder();
                        $redirectURL->buildFrom(SparkPage::Instance()->getPageURL());
                    }
                }

                if ($this->redirect_enabled) {
                    debug("Redirect logic enabled");
                    if ($redirectURL instanceof URLBuilder) {
                        debug("Using redirectURL: ".$redirectURL->url());
                        header("Location: " . $redirectURL->url());
                        exit;
                    }
                }
                else {
                    debug("Redirect logic disabled");
                }


            }
            else if ($process_status === IFormProcessor::STATUS_ERROR) {
                debug("Process status is error");
                throw new Exception($this->processor->getMessage());
            }

        }
        catch (Exception $e) {
            debug("Exception received: " . $e->getMessage());
            Session::SetAlert($e->getMessage());
            $this->error = $e->getMessage();
        }

    }

}

?>
