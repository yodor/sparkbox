<?php
include_once("lib/components/Component.php");
include_once("lib/input/renderers/IErrorRenderer.php");

class InputLabel extends Component implements IErrorRenderer
{

    protected $input = NULL;

    public $error_render_mode = IErrorRenderer::MODE_TOOLTIP;


    public function __construct(DataInput $input)
    {
        parent::__construct();
        $this->input = $input;
    }

    public function startRender()
    {
        $this->processErrorAttributes();

        parent::startRender();
        echo "<label>";
    }

    public function renderImpl()
    {
        echo tr($this->input->getLabel());

        $star = "";

        if ($this->input->getForm() && $this->input->getForm()->star_required) {
            $star = ($this->input->isRequired()) ? "<span class=required>*</span>" : "";
        }

        echo $star;

        if ($this->error_render_mode == IErrorRenderer::MODE_SPAN) {
            echo "<small class='error_details'>";
            echo tr($this->input->getError());
            echo "</small>";
        }

    }

    public function finishRender()
    {
        echo "</label>";
        parent::finishRender();
    }

    public function processErrorAttributes()
    {

        if ($this->input->haveError()) {

            if ($this->input instanceof ArrayDataInput) {
                $field_error = "Some elements of this collection have errors";
            }
            else {
                $field_error = $this->input->getError();
            }

            if (strlen($field_error) > 0) {
                $this->setAttribute("error", 1);

                if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {
                    $this->setAttribute("tooltip", tr($field_error));
                }
            }
        }
        else {
            $this->setAttribute("error", false);
        }

    }


}

?>