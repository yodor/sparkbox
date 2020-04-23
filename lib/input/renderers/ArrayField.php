<?php
include_once("lib/components/Component.php");
include_once("lib/input/renderers/IArrayFieldRenderer.php");
include_once("lib/components/renderers/ActionRenderer.php");

class ArrayField extends Component implements IArrayFieldRenderer
{
    /**
     * @var DataInput
     */
    protected $field = NULL;

    /**
     * @var array Component
     */
    protected $controls = array();

    /**
     * @var ActionRenderer|null
     */
    protected $action_renderer = NULL;

    const DEFAULT_CONTROL_ACTION = "Add";
    const DEFAULT_CONTROL_NAME = "Add";

    public function __construct()
    {
        parent::__construct();
        //$this->setClassName("InputField");

        $button_add = StyledButton::DefaultButton();
        $button_add->setType(StyledButton::TYPE_BUTTON);
        $button_add->setName(ArrayField::DEFAULT_CONTROL_NAME);
        $button_add->setAttribute("action", ArrayField::DEFAULT_CONTROL_ACTION);
        $this->addControl($button_add);

        $this->action_renderer = new ActionRenderer(new Action("Remove", "", array()));

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/ArrayField.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "lib/js/ArrayControls.js";
        return $arr;
    }

    public function addControl(Component $cmp)
    {
        $this->controls[$cmp->getName()] = $cmp;
    }

    /**'
     * @param string $name
     * @return Component
     */
    public function getControl(string $name)
    {
        return $this->controls[$name];
    }

    /**
     * @return array Component
     */
    public function getControls()
    {
        return $this->controls;
    }

    /**
     * @param DataInput $field
     * @throws Exception
     */
    public function renderField(DataInput $field)
    {
        if (!$field instanceof ArrayDataInput) {
            throw new Exception("ArrayInputField required for this control");
        }
        $this->setField($field);
        $this->render();
    }

    public function getField()
    {
        return $this->field;
    }

    public function setField(DataInput $field)
    {
        $this->field = $field;
    }

    public function renderValue(DataInput $field)
    {

    }


    public function renderControls()
    {
        if (!$this->field->allow_dynamic_addition) return;

        echo "<div class='ArrayControls' field='" . $this->field->getName() . "'>";

        foreach ($this->controls as $name => $cmp) {
            if (strcasecmp($name, ArrayField::DEFAULT_CONTROL_NAME) == 0) {
                $add_text = tr(ArrayField::DEFAULT_CONTROL_NAME) . " " . tr($this->field->getLabel());
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

        $field = new DataInput("render_source", $this->field->getLabel(), $this->field->isRequired());
        $renderer = clone $this->field->getRenderer();
        $field->setRenderer($renderer);

        $renderer->renderField($field);

        echo "<div class='Controls'>";
        $this->action_renderer->render();
        echo "</div>";

        echo "</div>";

    }

    public function renderArrayContents()
    {
        echo "<div class='ArrayContents' field='" . $this->field->getName() . "'>";
        $values = $this->field->getValue();

        $renderer = clone $this->field->getRenderer();

        if (is_array($values)) {

            foreach ($values as $idx => $value) {

                //class ElementSource is renamed to class Element from ArrayControls.js

                $field = new DataInput($this->field->getName() . "[$idx]", $this->field->getLabel(), $this->field->isRequired());

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
            onPageLoad(function () {
                let array_controls = new ArrayControls();
                array_controls.attachWith("<?php echo $this->field->getName();?>");
            });
        </script>
        <?php

    }

}

?>
