<?php
include_once("lib/components/Component.php");
include_once("lib/input/renderers/IArrayFieldRenderer.php");

class ArrayField extends Component implements IArrayFieldRenderer, IHeadRenderer
{
    protected $field = NULL;
    protected $controls = array();

    public function __construct()
    {
        parent::__construct();
        $this->setClassName("InputField");

        $button_add = StyledButton::DefaultButton();	
        $button_add->setType(StyledButton::TYPE_BUTTON);
        $button_add->setName("Insert");
        $button_add->setAttribute("action", "Insert");
        $this->addControl($button_add);
        
    }
    
    public function addControl(Component $cmp)
    {
        $this->controls[$cmp->getName()] = $cmp;
    }
    
    public function getControl($name)
    {
        return $this->controls[$name];
    }

    public function getControls()
    {
        return $this->controls;
    }

    public function renderField(InputField $field)
    {
        if (!$field instanceof ArrayInputField) {
            throw new Exception("ArrayInputField required for this control");
        }
        $this->setField($field);
        $this->render();
    }
    
    public function getField()
    {
        return $this->field;
    }
    
    public function setField(InputField $field)
    {
        $this->field = $field;
    }

    public function renderValue(InputField $field)
    {

    }

    public function renderScript()
    {
        echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/ArrayControls.js'></script>";
        echo "\n";
    }
    
    public function renderStyle()
    {
        echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/ArrayField.css' type='text/css'>";
        echo "\n";
    }
    
    public function renderControls()
    {
        if (!$this->field->allow_dynamic_addition) return;
        
        echo "<div class='ArrayControls' field='".$this->field->getName()."'>";

        foreach ($this->controls as $name=>$cmp) {
            if (strcasecmp($name, "Insert")==0) {
                $add_text = tr("Add")." ".tr($this->field->getLabel());
                $cmp->setText($add_text);
            }
            $cmp->render();
        }

        //$field_name = $this->field->getName();
        //$field_label = $this->field->getLabel();

        echo "</div>";
    }
    
    public function renderElementSource()
    {
        if (!$this->field->allow_dynamic_addition) return;
        
        echo "<div class='ElementSource'>";
        $field = new InputField("render_source", $this->field->getLabel(), $this->field->isRequired());
        $renderer = clone $this->field->getRenderer();
        $field->setRenderer($renderer);
        
        $renderer->renderField($field);
        
        echo "<div class='Controls'>";
            echo "<a class='ActionRenderer' action='Remove' >";
            echo tr("Remove");
            echo "</a>";
        echo "</div>";
            
        echo "</div>";
        
    }
    
    public function renderArrayContents()
    {
        echo "<div class='ArrayContents' field='".$this->field->getName()."'>";
        $values = $this->field->getValue();


        $renderer = clone $this->field->getRenderer();
        
        if (is_array($values)) {
        
            foreach($values as $idx=>$value) {
            
            //class ElementSource is renamed to class Element
            
                $field = new InputField($this->field->getName()."[$idx]", $this->field->getLabel(), $this->field->isRequired());
                
                $field->setError($this->field->getErrorAt($idx));
                
                $field->setValue($value);


                echo "<div class='Element' pos='$idx'>";
                
                    $renderer->renderField($field);

                    if ($this->field->allow_dynamic_addition) {
                    echo "<div class='Controls' >";
                    
                    echo "<a class='ActionRenderer' action='Remove' >";
                    echo tr("Remove");
                    echo "</a>";
                    
                    echo "</div>";
                    }
                    
                echo "</div>";
            
            }
        
        }
        echo "</div>";
    }
    
    public function renderImpl() 
    {
        $this->renderControls();
        $this->renderElementSource();
        $this->renderArrayContents();

        ?>
        <script type='text/javascript'>
        addLoadEvent(function(){
            var array_controls = new ArrayControls();
            array_controls.attachWith("<?php echo $this->field->getName();?>");
        });
        </script>
        <?php

    }

}
?>
