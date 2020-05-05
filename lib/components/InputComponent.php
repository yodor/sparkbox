<?php
include_once("lib/components/Component.php");
include_once("lib/input/DataInput.php");
include_once("lib/input/ArrayDataInput.php");

class InputComponent extends Component
{

    /**
     * @var DataInput
     */
    protected $input;

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
    }

    public function getInput(): DataInput
    {
        return $this->input;
    }

    public function renderImpl()
    {

        if ($this->input->getRenderer() instanceof HiddenField) {
        }
        else {
            $this->input->getLabelRenderer()->render();
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

        if ($this->input->content_after) {
            echo "<div class='content_after'>";
            echo $this->input->content_after;
            echo "</div>";
        }

        parent::finishRender();

    }

}

?>
