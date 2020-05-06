<?php
include_once("forms/processors/IFormProcessor.php");
include_once("beans/IDBTableEditor.php");

class FormProcessor implements IFormProcessor, IDBTableEditor
{

    protected $status = IFormProcessor::STATUS_NOT_PROCESSED;

    protected $message = "";

    protected $editID = -1;
    protected $bean = NULL;

    /**
     * @return string
     */
    public function getMessage()
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

    public function getStatus()
    {
        return $this->status;
    }

    public function setEditID(int $editID): void
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

    public function setBean(DBTableBean $bean): void
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

    }


    public function processForm(InputForm $form, $submit_name = "")
    {


        // 	  $method = $form->getRenderer()->getAttribute("method");

        if (strlen($submit_name) == 0) {
            $submit_name = $form->getRenderer()->getSubmitName($form);

            debug("Using form renderer submit key name");
        }

        debug("Using submit key name: '$submit_name'");

        if (isset($_REQUEST[$submit_name])) {

            debug("Key '$submit_name' found in _REQUEST");

            //default status

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
            debug("Setting STATUS_NOT_PROCESSED - key '$submit_name' not found in _REQUEST");
            $this->status = IFormProcessor::STATUS_NOT_PROCESSED;
        }

    }

    protected function processImpl(InputForm $form)
    {
        debug("FormProcessor::processImpl ...");

        if ($form->haveErrors()) {
            debug("FormProcessor::processImpl: " . get_class($form) . " form have fields with errors, throwing ...");

            $this->status = IFormProcessor::STATUS_ERROR;


            foreach ($form->getInputs() as $field_name => $field) {
                if ($field->haveError()) {
                    debug("Field: '$field_name': Error");
                }
            }

            throw new Exception("Form have errors. Ensure you input all required fields.");

        }
        else {

            $this->status = IFormProcessor::STATUS_OK;

            debug("FormProcessor::processImpl: " . get_class($form) . " does not have fields with errors. STATUS_FORM_OK");

        }
    }

}

?>