<?php
include_once("components/Component.php");
include_once("input/DataInput.php");
include_once("input/ArrayDataInput.php");

class InputComponent extends Component
{

    protected ?DataInput $input = null;
    protected ?InputLabel $label_renderer = null;

    public function __construct(DataInput $input = NULL)
    {
        parent::__construct(false);

        if ($input) {
            $this->setDataInput($input);
        }
    }

    public function setDataInput(DataInput $input) : void
    {
        $this->input = $input;

        $this->setAttribute("field",$input->getName());

        if ($input->isRequired()) {
            $this->setAttribute("required", 1);
        }
        else {
            $this->removeAttribute("required");
        }

        $this->label_renderer = new InputLabel($input);
    }

    public function getDataInput(): ?DataInput
    {
        return $this->input;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function setLabelRenderer(InputLabel $label_renderer) : void
    {
        $this->label_renderer = $label_renderer;
    }

    public function getLabelRenderer(): ?InputLabel
    {
        return $this->label_renderer;
    }

    public function getInput(): ?DataInput
    {
        return $this->input;
    }

    public function renderImpl()
    {

        if (!($this->input->getRenderer() instanceof HiddenField)) {

            $this->label_renderer->render();
        }

        $this->input->getRenderer()->render();

    }

    public function finishRender()
    {

        $field = $this->input;
        $field_name = $field->getName();

        if (TRANSLATOR_ENABLED && $field->translatorEnabled()) {

            echo "<a class='Action' action='TranslateBeanField' field='$field_name'>";
            echo tr("Translate");
            echo "</a>";

        }

        parent::finishRender();

    }

}

?>
