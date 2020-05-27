<?php
include_once("components/Component.php");
include_once("input/DataInput.php");
include_once("input/ArrayDataInput.php");

class InputComponent extends Component
{

    /**
     * @var DataInput
     */
    protected $input;

    /**
     * @var InputLabel
     */
    protected $label_renderer;

    public function __construct(DataInput $input = NULL)
    {
        parent::__construct();
        if ($input) {
            $this->setDataInput($input);
        }
    }

    public function setDataInput(DataInput $input)
    {
        $this->input = $input;

        $this->attributes["field"] = $input->getName();

        if ($input->isRequired()) {
            $this->attributes["required"] = 1;
        }
        else {
            $this->attributes["required"] = "";
        }

        $this->label_renderer = new InputLabel($input);
    }

    public function getDataInput(): DataInput
    {
        return $this->input;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function setLabelRenderer(InputLabel $label_renderer)
    {
        $this->label_renderer = $label_renderer;
    }

    public function getLabelRenderer(): InputLabel
    {
        return $this->label_renderer;
    }

    public function getInput(): DataInput
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
