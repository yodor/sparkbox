<?php
include_once("components/TextComponent.php");
include_once("components/renderers/items/DataIteratorItem.php");

class CheckItem extends DataIteratorItem
{

    /**
     * Checkbox hidden force submit empty value for non-checked items
     * @var Input
     */
    protected Input $hidden;

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
        parent::__construct(false);

        $this->hidden = new Input("hidden");
        $this->hidden->setValue("");

        $this->items()->append($this->hidden);

        $this->input = new Input("checkbox");
        $this->input->setValue("");

        $this->items()->append($this->input);

        $this->span = new TextComponent();
        $this->span->setComponentClass("");
        $this->items()->append($this->span);

    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        $this->hidden->setName($this->name);
        $this->hidden->setValue("");

        $this->input->setName($this->name);
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