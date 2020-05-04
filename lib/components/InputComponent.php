<?php
include_once("lib/components/Component.php");
include_once("lib/input/DataInput.php");
include_once("lib/input/ArrayDataInput.php");


class InputComponent extends Component
{

    /**
     * @var DataInput
     */
    protected $field;

    public $render_label = true;
    public $render_remove = false;

    const RENDER_INPUT = 1;
    const RENDER_VALUE = 2;
    //const RENDER_ARRAY = 3;
    public $render_mode = InputComponent::RENDER_INPUT;

//    protected $array_renderer = NULL;

    public function __construct()
    {
        parent::__construct();

//        $this->array_renderer = new ArrayField();
    }

    public function setField(DataInput $field)
    {
        $this->field = $field;
        $this->attributes["field"] = $field->getName();

        if ($field->isRequired()) {
            $this->attributes["required"] = 1;
        }
        else {
            $this->attributes["required"] = "";
        }

    }

//    public function getArrayRenderer()
//    {
//        return $this->array_renderer;
//    }
//
//    public function setArrayRenderer(IArrayFieldRenderer $renderer)
//    {
//        $this->array_renderer = $renderer;
//    }

    public function getField() : DataInput
    {
        return $this->field;
    }

    public function renderImpl()
    {

        $renderer = null;

        if ($this->field instanceof ArrayDataInput) {
            $renderer = clone $this->field->getRenderer();
        }
        else {
            $renderer = $this->field->getRenderer();
        }

        if ($this->render_label) {
            if ($renderer instanceof HiddenField) {
            }
            else {
                $this->field->getLabelRenderer()->renderLabel($this->field);
            }
        }

        if ($this->render_mode === InputComponent::RENDER_INPUT) {

            $renderer->renderField($this->field);

        }
        else if ($this->render_mode === InputComponent::RENDER_VALUE) {

            if ($renderer instanceof HiddenField) {
                //
            }
            else {
                $renderer->renderValue($this->field);
            }
        }


    }

    public function finishRender()
    {

        $field = $this->field;
        $field_name = $field->getName();

        if (TRANSLATOR_ENABLED && $field->translatorEnabled() && ($field->getForm() instanceof InputForm)) {
            $form = $field->getForm();
            $editID = $form->getEditID();

            if ($editID > 0 && $form->getBean() instanceof DBTableBean) {

                echo "<a class='ActionRenderer' action='TranslateBeanField' field='$field_name'>";
                echo tr("Translate");
                echo "</a>";

            }

        }

        if ($this->field->content_after) {
            echo "<div class='content_after'>";
            echo $this->field->content_after;
            echo "</div>";
        }

        parent::finishRender();

    }


}

?>
