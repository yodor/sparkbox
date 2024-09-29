<?php
include_once("components/Component.php");
include_once("input/renderers/IErrorRenderer.php");

class InputLabel extends Component implements IErrorRenderer
{

    protected DataInput $input;

    protected int $error_render_mode = IErrorRenderer::MODE_TOOLTIP;

    public function __construct(DataInput $input)
    {
        parent::__construct(false);
        $this->setComponentClass("InputLabel");
        $this->setTagName("LABEL");

        $this->input = $input;
    }

    protected function processErrorAttributes() : void
    {

        if (!$this->input->haveError()) return;

        $this->setAttribute("error", 1);

        if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {

            $error = "";
            if ($this->input instanceof ArrayDataInput) {
                $error = $this->input->getErrorText();
            }
            else {
                $error = tr($this->input->getError());
            }
            $this->setTooltipText($error);

        }

    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->processErrorAttributes();
    }

    public function renderImpl()
    {
        echo tr($this->input->getLabel());

        if ($this->input->isRequired()) {
            if ($this->input->getForm() && $this->input->getForm()->star_required) {
                echo "<span class=required>*</span>";
            }
        }

        if ($this->input->haveError() && $this->error_render_mode == IErrorRenderer::MODE_SPAN) {
            echo "<small class='error_details'>";
            echo tr($this->input->getError());
            echo "</small>";
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

?>
