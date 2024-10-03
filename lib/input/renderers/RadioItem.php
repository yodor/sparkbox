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

    protected function processAttributes(): void
    {
        parent::processAttributes();

        /**
         * this->name is assigned from DataIteratorField during renderItems
         * copy from this name and remove the attribute
         */
        $this->input->setName($this->name);
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