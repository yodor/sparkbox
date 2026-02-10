<?php

include_once("responders/json/JSONResponder.php");
include_once("forms/InputForm.php");
include_once("forms/processors/FormProcessor.php");
include_once("forms/renderers/FormRenderer.php");

abstract class JSONFormResponder extends JSONResponder
{

    /**
     * @var InputForm
     */
    protected InputForm $form;

    /**
     * @var FormProcessor
     */
    protected FormProcessor $proc;

    public function __construct()
    {

        parent::__construct();

        $this->form = $this->createForm();

        $this->setupFormRenderer();

        $this->proc = new FormProcessor();

    }

    abstract protected function createForm() : InputForm;

    /**
     * Setup form renderer
     */
    protected function setupFormRenderer(): void
    {
        $fr = new FormRenderer($this->form);
        $fr->getSubmitLine()->setRenderEnabled(false);
    }

    public function _render(JSONResponse $resp): void
    {
        $this->form->getRenderer()->render();
    }

    public function _submit(JSONResponse $resp): void
    {

        $this->proc->process($this->form);
        if ($this->proc->getStatus() == IFormProcessor::STATUS_OK) {
            $this->onProcessSuccess($resp);
        }
        else if ($this->proc->getStatus() == IFormProcessor::STATUS_ERROR) {
            $this->onProcessError($resp);
        }
    }

    protected function onProcessSuccess(JSONResponse $resp): void
    {
        //do stuff with form data in subclass
        $resp->message = "success";
    }

    protected function onProcessError(JSONResponse $resp): void
    {
        $resp->message = $this->proc->getMessage();
        $this->form->getRenderer()->render();
    }

}
?>