<?php
include_once("components/Component.php");
include_once("components/Action.php");

class ArrayField extends InputField
{

    /**
     * @var DataInput
     */
    protected $input;

    /**
     * @var Container
     */
    protected $controls;

    /**
     * Remove element action of the element source
     * @var Action
     */
    protected $remove_action;

    const DEFAULT_CONTROL_ACTION = "Add";
    const DEFAULT_CONTROL_NAME = "Add";
    const DEFAULT_CONTROL_TEXT = "Add";

    /**
     * @var InputField
     */
    protected $element_renderer = NULL;

    protected $dynamic_addition = TRUE;

    public function __construct(InputField $field)
    {
        parent::__construct($field->getInput());

        $this->element_renderer = $field;

        $this->controls = new Container();
        $this->controls->setClassName("ArrayControls");

        $button_add = new ColorButton();
        $button_add->setType(ColorButton::TYPE_BUTTON);
        $button_add->setName(ArrayField::DEFAULT_CONTROL_NAME);
        $button_add->setAttribute("action", ArrayField::DEFAULT_CONTROL_ACTION);
        $button_add->setContents(ArrayField::DEFAULT_CONTROL_TEXT);

        $this->addControl($button_add);

        $this->remove_action = new Action("Remove");
        //$this->action->getURLBuilder()->setKeepRequestParams(false);

    }

    public function enableDynamicAddition(bool $mode)
    {
        $this->dynamic_addition = $mode;
    }

    public function isDynamicAdditionEnabled(): bool
    {
        return $this->dynamic_addition;
    }

    public function setElementRenderer(InputField $renderer)
    {
        $this->element_renderer = $renderer;
    }

    public function getElementRenderer(): ?InputField
    {
        return $this->element_renderer;
    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ArrayField.css";
        return $arr;
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/ArrayField.js";
        return $arr;
    }

    /**
     * Add component to the controls container
     * @param Component $cmp
     */
    public function addControl(Component $cmp)
    {
        $this->controls->append($cmp);
    }

    /**'
     * @param string $name
     * @return Component
     */
    public function getControl(string $name)
    {
        return $this->controls->getByName($name);
    }

    /**
     * @return Container
     */
    public function getControls(): Container
    {
        return $this->controls;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function renderControls()
    {
        if (!$this->dynamic_addition) return;

        $this->controls->setAttribute("field", $this->input->getName());

        $this->controls->render();

    }

    public function renderElementSource()
    {
        if (!$this->dynamic_addition) return;

        echo "<div class='ElementSource'>";

        $fake_input = new DataInput("render_source", $this->input->getLabel(), $this->input->isRequired());

        $renderer = clone $this->element_renderer;
        $renderer->setInput($fake_input);

        $renderer->render();

        echo "<div class='Controls'>";
        $this->remove_action->render();
        echo "</div>";

        echo "</div>";

    }

    public function renderArrayContents()
    {
        echo "<div class='ArrayContents' field='" . $this->input->getName() . "'>";

        $values = $this->input->getValue();

        if (is_array($values)) {

            $pos = -1;
            foreach ($values as $idx => $value) {

                //class ElementSource is renamed to class Element from ArrayControls.js

                $pos++;

                $element_input = new DataInput($this->input->getName() . "[$idx]", $this->input->getLabel(), $this->input->isRequired());

                $element_input->setError($this->input->getErrorAt($idx));

                $element_input->setValue($value);

                echo "<div class='Element' pos='$pos' key='$idx'>";

                $renderer = clone $this->element_renderer;
                $renderer->setInput($element_input);

                $renderer->render();

                if ($this->dynamic_addition) {
                    echo "<div class='Controls' >";
                    $this->remove_action->render();
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
                let array_field = new ArrayField();
                array_field.setField("<?php echo $this->input->getName();?>");
                array_field.initialize();
                //array_field selector is now .ArrayField[field='$this->input->getName()']
            });
        </script>
        <?php

    }

}

?>