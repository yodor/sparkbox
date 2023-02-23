<?php

include_once("responders/json/JSONResponder.php");
include_once("forms/InputForm.php");
include_once("forms/processors/FormProcessor.php");
include_once("forms/renderers/FormRenderer.php");
include_once("dialogs/JSONFormDialog.php");

abstract class JSONFormResponder extends JSONResponder
{

    /**
     * @var InputForm
     */
    protected $form;

    /**
     * @var FormProcessor
     */
    protected $proc;

    public function __construct(string $cmd)
    {
        parent::__construct($cmd);

        $this->form = $this->createForm();

        $this->setupFormRenderer();

        $this->proc = new FormProcessor();

        $dialog = new JSONFormDialog();
    }

    abstract protected function createForm() : InputForm;

    /**
     * Setup form renderer
     */
    protected function setupFormRenderer()
    {
        $fr = new FormRenderer($this->form);
        $fr->getSubmitLine()->setEnabled(false);
    }

    public function _render(JSONResponse $resp)
    {
        $this->form->getRenderer()->render();
    }

    public function _submit(JSONResponse $resp)
    {

        $this->proc->process($this->form);
        if ($this->proc->getStatus() == IFormProcessor::STATUS_OK) {
            $this->onProcessSuccess($resp);
        }
        else if ($this->proc->getStatus() == IFormProcessor::STATUS_ERROR) {
            $this->onProcessError($resp);
        }
    }

    protected function onProcessSuccess(JSONResponse $resp)
    {
        //do stuff with form data in subclass
        $resp->message = "success";
    }

    protected function onProcessError(JSONResponse $resp)
    {
        $resp->message = $this->proc->getMessage();
        $this->form->getRenderer()->render();
    }

}
?>