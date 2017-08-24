<?php
include_once("lib/components/Component.php");
include_once("lib/input/InputField.php");
include_once("lib/input/ArrayInputField.php");
include_once("lib/input/renderers/ArrayField.php");

class InputComponent extends Component implements IHeadRenderer
{

    protected $field = NULL;

    public $render_label = true;
    public $render_remove = false;

    const RENDER_INPUT = 1;
    const RENDER_VALUE = 2;
    const RENDER_ARRAY = 3;
    public $render_mode = InputComponent::RENDER_INPUT;

    protected $array_renderer = NULL;

    public function __construct()
    {
        parent::__construct();

        $this->array_renderer = new ArrayField();
    }

    public function renderScript()
    {

    }

    public function renderStyle()
    {
        echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/InputRenderer.css' type='text/css' >";
        echo "\n";
    }

    public function setField(InputField $field)
    {
        $this->field = $field;
        $this->attributes["field"]=$field->getName();
        
        if ($field->isRequired()) {
            $this->attributes["required"]=1;
        }
        else {
            $this->attributes["required"]="";
        }

    }

    public function getArrayRenderer()
    {
        return $this->array_renderer;
    }
    
    public function setArrayRenderer(IArrayFieldRenderer $renderer)
    {
        $this->array_renderer = $renderer;
    }
    
    public function getField()
    {
        return $this->field;
    }

    public function renderImpl()
    {

        $renderer = $this->field->getRenderer();
        $field = $this->field;

        if ($this->render_label) {
            if ($renderer instanceof HiddenField) {}
            else {
                $field->getLabelRenderer()->renderLabel($field);
            }
        }

        if ($field instanceof ArrayInputField) {
            //use private renderer if set 
            
            if ($field->getArrayRenderer() instanceof IArrayFieldRenderer) {
                $renderer = clone $field->getArrayRenderer();
            }
            
            if ($renderer instanceof IArrayFieldRenderer){
                
            }
            else {
                $renderer = clone $this->array_renderer;
            }
        }

        if ($this->render_mode === InputComponent::RENDER_INPUT) {

            $renderer->renderField($field);

        }
        else if ($this->render_mode === InputComponent::RENDER_VALUE) {

            if ($field->getRenderer() instanceof HiddenField) {}
            else {
                $renderer->renderValue($field);
            }
            
        }


    }

    public function finishRender()
    {
        
        $field = $this->field;
        $field_name = $field->getName();

        if (TRANSLATOR_ENABLED && $field->translatorEnabled() && ($field->getForm() instanceof InputForm) ) {
            $form = $field->getForm();
            $editID = $form->getEditID();

            if ($editID > 0 && $form->getEditBean() instanceof DBTableBean) {

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
