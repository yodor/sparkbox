<?php
include_once("components/Component.php");
include_once("components/Action.php");

class ArrayField extends InputField
{

    protected Container $controls;

    protected Container $array_contents;

    /**
     * Element remove action
     * @var Action
     */
    protected Action $remove_action;

    const string DEFAULT_CONTROL_ACTION = "Add";
    const string DEFAULT_CONTROL_NAME = "Add";
    const string DEFAULT_CONTROL_TEXT = "Add";

    protected InputField $element_renderer;

    protected Container $element_controls;

    public function __construct(InputField $field)
    {
        parent::__construct($field->getDataInput());

        $this->element_renderer = $field;

        $this->controls = new Container(false);
        $this->controls->setComponentClass("ArrayControls");

        $button_add = new ColorButton();
        $button_add->setType(ColorButton::TYPE_BUTTON);
        $button_add->setName(ArrayField::DEFAULT_CONTROL_NAME);
        $button_add->setAttribute("action", ArrayField::DEFAULT_CONTROL_ACTION);
        $button_add->setContents(ArrayField::DEFAULT_CONTROL_TEXT);

        $this->controls->items()->append($button_add);

        $this->remove_action = new Action("Remove");

        $this->element_controls = new Container(false);
        $this->element_controls->setComponentClass("Controls");
        $this->element_controls->items()->append($this->remove_action);

        $this->items()->append($this->controls);

        $element_source = new ClosureComponent($this->renderElementSource(...));
        $element_source->setComponentClass("ElementSource");
        $this->items->append($element_source);

        $this->array_contents = new ClosureComponent($this->renderArrayContents(...));
        $this->array_contents->setComponentClass("ArrayContents");
        $this->items->append($this->array_contents);
    }

    public function enableDynamicAddition(bool $mode) : void
    {
        $this->controls->setRenderEnabled($mode);
        $this->element_controls->setRenderEnabled($mode);
    }

    public function isDynamicAdditionEnabled(): bool
    {
        return $this->controls->isRenderEnabled();
    }

    public function setElementRenderer(InputField $renderer) : void
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

    public function controls() : Container
    {
        return $this->controls;
    }

    public function elementControls() : Container
    {
        return $this->element_controls;
    }

    public function elementRemoveAction() : Action
    {
        return $this->remove_action;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->controls->setAttribute("field", $this->dataInput->getName());
        $this->array_contents->setAttribute("field", $this->dataInput->getName());
    }

    protected function renderElementSource() : void
    {
        $fake_input = new DataInput("render_source", $this->dataInput->getLabel(), $this->dataInput->isRequired());

        $renderer = clone $this->element_renderer;
        $renderer->setDataInput($fake_input);

        $renderer->render();

        $this->element_controls->render();
    }

    protected function renderArrayContents() : void
    {

        $values = $this->dataInput->getValue();

        if (!is_array($values)) return;
        if (!($this->dataInput instanceof ArrayDataInput)) return;

        $element_input = new DataInput($this->dataInput->getName() , $this->dataInput->getLabel(), $this->dataInput->isRequired());

        $renderer = clone $this->element_renderer;

        $pos = -1;
        foreach ($values as $idx => $value) {

            $pos++;

            $element_input->setName($this->dataInput->getName()."[$idx]");
            $element_input->setError($this->dataInput->getErrorAt($idx));
            $element_input->setValue($value);

            echo "<div class='Element' pos='$pos' key='$idx'>";

            $renderer->setDataInput($element_input);
            $renderer->render();

            $this->element_controls->render();

            echo "</div>";

        }

    }


    public function finishRender()
    {
        parent::finishRender();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let array_field = new ArrayField();
                array_field.setField("<?php echo $this->dataInput->getName();?>");
                array_field.initialize();
                //array_field selector is now .ArrayField[field='$this->input->getName()']
            });
        </script>
        <?php
    }

}

?>
