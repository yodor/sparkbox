<?php
include_once("lib/forms/processors/IFormProcessor.php");

class FormProcessor implements IFormProcessor
{

    protected $status = IFormProcessor::STATUS_NOT_PROCESSED;

    protected $message = "";

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

    public function __construct()
    {

    }


    public function processForm(InputForm $form, $submit_name = "")
    {


        // 	  $method = $form->getRenderer()->getAttribute("method");

        if (strlen($submit_name) == 0) {
            $submit_name = $form->getRenderer()->getSubmitName($form);
        }

        debug("FormProcessor::processForm with submit_name: $submit_name");

        if (isset($_REQUEST[$submit_name])) {

            debug("FormProcessor::processForm | '$submit_name' key found in the REQUEST. Start processing ...");

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
            debug("FormProcessor::processForm: | '$submit_name' key not found in REQUEST. Skipping form processing ....");
            $this->status = IFormProcessor::STATUS_NOT_PROCESSED;
        }
    }

    protected function processImpl(InputForm $form)
    {
        debug("FormProcessor::processImpl ...");

        if ($form->haveErrors()) {
            debug("FormProcessor::processImpl: " . get_class($form) . " form have fields with errors, throwing ...");

            $this->status = IFormProcessor::STATUS_ERROR;


            foreach ($form->getFields() as $field_name => $field) {
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