<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/ActionRenderer.php");

class ArrayField extends InputField
{
    /**
     * @var DataInput
     */
    protected $input = NULL;

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

    /**
     * @var InputField
     */
    protected $item_renderer = NULL;

    public function __construct(InputField $field)
    {
        parent::__construct($field);

        $this->item_renderer = $field;

        $button_add = StyledButton::DefaultButton();
        $button_add->setType(StyledButton::TYPE_BUTTON);
        $button_add->setName(ArrayField::DEFAULT_CONTROL_NAME);
        $button_add->setAttribute("action", ArrayField::DEFAULT_CONTROL_ACTION);
        $this->addControl($button_add);

        $this->action_renderer = new ActionRenderer(new Action("Remove", "", array()));

    }

    public function setItemRenderer(InputField $renderer)
    {
        $this->item_renderer = $renderer;
    }

    public function getItemRenderer() : ?InputField
    {
        return $this->item_renderer;
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

//    /**
//     * @param DataInput $field
//     * @throws Exception
//     */
//    public function renderField(DataInput $field)
//    {
//        if (!$field instanceof ArrayDataInput) {
//            throw new Exception("ArrayDataInput required for this renderer");
//        }
//        $this->setField($field);
//        $this->render();
//    }

    public function getInput()
    {
        return $this->input;
    }

//    public function setField(DataInput $field)
//    {
//        $this->field = $field;
//
//    }

    public function renderControls()
    {
        if (!$this->input->allow_dynamic_addition) return;

        echo "<div class='ArrayControls' field='" . $this->input->getName() . "'>";

        foreach ($this->controls as $name => $cmp) {
            if (strcasecmp($name, ArrayField::DEFAULT_CONTROL_NAME) == 0) {
                $add_text = tr(ArrayField::DEFAULT_CONTROL_NAME) . " " . tr($this->input->getLabel());
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
        if (!$this->input->allow_dynamic_addition) return;

        echo "<div class='ElementSource'>";

        $fake_input = new DataInput("render_source", $this->input->getLabel(), $this->input->isRequired());

        $renderer = clone $this->item_renderer;
        $renderer->setInput($fake_input);

        $renderer->render();

        echo "<div class='Controls'>";
        $this->action_renderer->render();
        echo "</div>";

        echo "</div>";

    }

    public function renderArrayContents()
    {
        echo "<div class='ArrayContents' field='" . $this->input->getName() . "'>";

        $values = $this->input->getValue();

        if (is_array($values)) {

            foreach ($values as $idx => $value) {

                //class ElementSource is renamed to class Element from ArrayControls.js

                $element_input = new DataInput($this->input->getName() . "[$idx]", $this->input->getLabel(), $this->input->isRequired());

                $element_input->setError($this->input->getErrorAt($idx));

                $element_input->setValue($value);

                echo "<div class='Element' pos='$idx'>";

                $renderer = clone $this->item_renderer;
                $renderer->setInput($element_input);

                $renderer->render();

                if ($this->input->allow_dynamic_addition) {
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
                array_controls.attachWith("<?php echo $this->input->getName();?>");
            });
        </script>
        <?php

    }

}

?>
