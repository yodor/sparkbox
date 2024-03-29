<?php
include_once("forms/processors/IFormProcessor.php");
include_once("beans/IBeanEditor.php");

class FormProcessor implements IFormProcessor, IBeanEditor
{

    protected $status = IFormProcessor::STATUS_NOT_PROCESSED;

    protected $message = "";

    protected $editID = -1;
    protected $bean = NULL;

    protected $sessionEnabled = false;

    //redirect to clean the url after storing session data. only works if session is enabled
    protected $redirectEnabled = true;

    /**
     * Store/Restore the form input values to session during process call
     *
     * @param bool $mode
     */
    public function enableSession(bool $mode)
    {
        $this->sessionEnabled = $mode;
    }

    public function isSessionEnabled() : bool
    {
        return $this->sessionEnabled;
    }

    /**
     * Enable redirection to the same url cleaned from GET variables after successful process
     * @param bool $mode
     */
    public function setRedirectEnabled(bool $mode)
    {
        $this->redirectEnabled = $mode;
    }

    public function isRedirectEnabled() : bool
    {
        return $this->redirectEnabled;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setEditID(int $editID)
    {
        $this->editID = $editID;
    }

    /**
     * @return int
     */
    public function getEditID(): int
    {
        return $this->editID;
    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
    }

    /**
     * @return DBTableBean
     */
    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }

    public function __construct()
    {
        $this->status = IFormProcessor::STATUS_NOT_PROCESSED;

    }

    public function process(InputForm $form)
    {
        $submitKey = FormRenderer::SUBMIT_NAME;
        $submitValue = "";
        if (isset($_REQUEST[$submitKey])) {
            $submitValue = $_REQUEST[$submitKey];
        }

        $form_name = $form->getName();


        if ($this->sessionEnabled) {
            $this->restoreSessionData($form);
        }

        if (strcmp($submitValue, $form_name) == 0) {
            debug("Loading form with _REQUEST values - key '$submitKey' value = Form name: '$form_name' ");

            try {

                //validate values coming from user input
                $form->loadPostData($_REQUEST);
                $form->validate();

                $this->processImpl($form);

                $this->setStatus(IFormProcessor::STATUS_OK);

                if ($this->sessionEnabled) {
                    $this->storeSessionData($form);

                    if ($this->redirectEnabled) {
                        $url = new URLBuilder();
                        $url->buildFrom(SparkPage::Instance()->getPageURL());
                        foreach ($form->getInputs() as $inputName=>$input){
                            $url->remove($inputName);
                        }
                        $url->remove(FormRenderer::SUBMIT_NAME);
                        header("Location: ".$url->url());
                        exit;
                    }
                }

            }
            catch (Exception $e) {

                $this->setMessage($e->getMessage());
                $this->status = IFormProcessor::STATUS_ERROR;
            }

        }
        else {
            debug("Setting STATUS_NOT_PROCESSED - _REQUEST key '$submitKey' not found or not equal to '{$form->getName()}' ");
            $this->status = IFormProcessor::STATUS_NOT_PROCESSED;
        }

    }

    protected function restoreSessionData(InputForm $form)
    {
        $form_name = $form->getName();
        debug("Restoring values from session - InputForm['$form_name']");

        if (Session::Contains($form_name)) {
            $values = Session::Get($form_name);
            $values = unserialize($values);

            try {

                //validate values coming from user input
                foreach($values as $inputName=>$inputValue) {
                    if ($form->haveInput($inputName)) {
                        $form->getInput($inputName)->setValue($inputValue);
                    }
                }
                $form->validate();

            }
            catch (Exception $e) {
                $form->clear();
                debug("Session data could not be restored for this form '$form_name'");

            }
        }
    }

    protected function storeSessionData(InputForm $form)
    {

        $values = array();

        $form_name = $form->getName();
        debug("Storing values to session - InputForm['$form_name']");

        foreach ($form->getInputs() as $inputName=>$input){
            if (!($input instanceof DataInput)) continue;
            $inputValue = $input->getValue();
            $values[$inputName] = $inputValue;

        }

        Session::Set($form_name, serialize($values));

    }

    protected function processImpl(InputForm $form)
    {

        if ($form->haveErrors()) {

            $this->status = IFormProcessor::STATUS_ERROR;

            $error_inputs = array();
            foreach ($form->getInputs() as $field_name => $field) {
                if ($field->haveError()) {
                    $error_inputs[] = $field->getName();
                }
            }
            debug("STATUS_ERROR - Form '{$form->getName()}' - error found in DataInput(s): ", $error_inputs);

            throw new Exception("Please make input in all required fields");

        }
        else {

            $this->status = IFormProcessor::STATUS_OK;

            debug("STATUS_OK - Form '{$form->getName()}' ");

        }
    }

}

?>