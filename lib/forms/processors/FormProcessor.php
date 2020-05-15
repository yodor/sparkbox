<?php
include_once("forms/processors/IFormProcessor.php");
include_once("beans/IBeanEditor.php");

class FormProcessor implements IFormProcessor, IBeanEditor
{

    protected $status = IFormProcessor::STATUS_NOT_PROCESSED;

    protected $message = "";

    protected $editID = -1;
    protected $bean = NULL;

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

        if (strcmp($submitValue, $form_name) == 0) {
            debug("Loading form with _REQUEST values - key '$submitKey' value = Form name: '$form_name' ");

            try {

                //validate values comming from user input
                $form->loadPostData($_REQUEST);
                $form->validate();

                $this->processImpl($form);

                $this->setStatus(IFormProcessor::STATUS_OK);
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