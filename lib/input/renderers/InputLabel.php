<?php
include_once("components/Component.php");
include_once("input/renderers/IErrorRenderer.php");

class InputLabel extends Component implements IErrorRenderer
{

    protected $tagName = "LABEL";

    protected $input = NULL;

    protected $error_render_mode = IErrorRenderer::MODE_TOOLTIP;

    public function __construct(DataInput $input)
    {
        parent::__construct();
        $this->input = $input;
    }

    public function processErrorAttributes()
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
            $this->setAttribute("tooltip", $error);

        }

    }

    public function startRender()
    {
        $this->processErrorAttributes();

        parent::startRender();

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


    public function setErrorRenderMode(int $mode)
    {
        $this->error_render_mode = $mode;
    }

    public function getErrorRenderMode(): int
    {
        return $this->error_render_mode;
    }

}

?>