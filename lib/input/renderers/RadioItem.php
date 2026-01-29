<?php
include_once("components/TextComponent.php");
include_once("components/renderers/items/DataIteratorItem.php");
include_once("input/renderers/Input.php");

class RadioItem extends DataIteratorItem
{
    /**
     * Checkbox
     * @var Input
     */
    protected Input $input;

    /**
     * Label for checkbox
     * @var TextComponent
     */
    protected TextComponent $span;

    public function __construct()
    {
        parent::__construct();

        $this->setClassName("RadioItem");

        $this->input = new Input();
        $this->input->setType("radio");
        $this->input->setValue("");
        $this->items()->append($this->input);

        $this->span = new TextComponent();
        $this->span->setComponentClass("");
        $this->items()->append($this->span);
    }

    public function getInput() : Component
    {
        return $this->input;
    }


    /**
     * Override DataIteratorItem naming and return just the dataInput name as
     * set from the DataIteratorField during renderItems()
     *
     * DataIteratorItem default implementation sets named keys as name ie name[1],name[2]
     * but radio buttons are single selection model and are grouped using the same name
     * @return string
     */
    protected function createInputName() : string
    {
        return $this->getName();
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        //DataIteratorField assigns name = dataInput name during renderItems
        //passthrough the name to the actual inputs
        $this->removeAttribute("name");

        $this->input->setValue(attributeValue($this->value));

        if ($this->isSelected()) {
            $this->input->setAttribute("checked", "");
        }
        else {
            $this->input->removeAttribute("checked");
        }

        $this->span->setContents(attributeValue($this->label));
    }
}

?>