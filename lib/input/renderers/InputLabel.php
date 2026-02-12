<?php
include_once("components/LabelSpan.php");
include_once("input/renderers/IErrorRenderer.php");

class InputLabel extends LabelSpan implements IErrorRenderer
{

    protected ?DataInput $dataInput;

    protected int $error_render_mode = IErrorRenderer::MODE_TOOLTIP;

    protected Component $error_details;

    public function __construct(?DataInput $input = null)
    {
        parent::__construct();
        $this->setComponentClass("InputLabel");
        //$this->setTagName("LABEL");

        if ($input instanceof DataInput) {
            $this->setDataInput($input);
        }

        $this->span->setContents("*");
        $this->span->setComponentClass("required");
        $this->span->setRenderEnabled(false);

        $this->error_details = new Component(false);
        $this->error_details->setTagName("small");
        $this->error_details->setComponentClass("error_details");
        $this->error_details->setRenderEnabled(false);

        $this->items()->append($this->error_details);

    }

    public function setDataInput(DataInput $input) : void
    {
        $this->dataInput = $input;
    }

    public function getDataInput(): ?DataInput
    {
        return $this->dataInput;
    }

    protected function processErrorAttributes() : void
    {

        if (!$this->dataInput->haveError()) return;

        $this->setAttribute("error", 1);

        $error = "";
        if ($this->dataInput instanceof ArrayDataInput) {
            $error = $this->dataInput->getErrorText();
        }
        else {
            $error = $this->dataInput->getError();
        }

        if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {
            $this->setTooltip($error);
        }
        else {
            $this->error_details->setContents($error);
            $this->error_details->setRenderEnabled(true);
        }

    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->processErrorAttributes();

//        $this->label->setAttribute("for", $this->dataInput->getName());

        $this->label->setContents($this->dataInput->getLabel());

        if ($this->dataInput->isRequired()) {
            if ($this->dataInput->getForm() && $this->dataInput->getForm()->star_required) {
                $this->span->setRenderEnabled(true);
            }
        }

        $id = $this->dataInput->getID();
        if ($id) {
            $this->label->setAttribute("for", $id);
        }
    }

    public function setErrorRenderMode(int $mode) : void
    {
        $this->error_render_mode = $mode;
    }

    public function getErrorRenderMode(): int
    {
        return $this->error_render_mode;
    }

}