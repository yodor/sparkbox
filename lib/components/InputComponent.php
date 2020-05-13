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

    public function __construct(DataInput $input)
    {
        parent::__construct();
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

        if (! ($this->input->getRenderer() instanceof HiddenField)){

            $this->label_renderer->render();
        }

        $this->input->getRenderer()->render();

    }

    public function finishRender()
    {

        $field = $this->input;
        $field_name = $field->getName();

        if (TRANSLATOR_ENABLED && $field->translatorEnabled() && $field->getForm()) {
            $form = $field->getForm();
            $editID = $form->getEditID();

            if ($editID > 0 && $form->getBean()) {

                echo "<a class='ActionRenderer' action='TranslateBeanField' field='$field_name'>";
                echo tr("Translate");
                echo "</a>";

            }

        }

//        if ($this->input->content_after) {
//            echo "<div class='content_after'>";
//            echo $this->input->content_after;
//            echo "</div>";
//        }

        parent::finishRender();

    }

}

?>
