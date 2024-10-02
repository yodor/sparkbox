<?php
include_once("input/renderers/RadioField.php");
include_once("input/renderers/CheckItem.php");

class CheckField extends RadioField
{

    /**
     * Checkbox hidden force submit empty value for non-checked items
     * @var Input
     */
    protected Input $hidden;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setItemRenderer(new CheckItem());

        $this->hidden = new Input("hidden");
        $this->hidden->setValue("");

       // $this->elements->items()->prepend($this->hidden);
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        if (!$this->iterator) {
            $this->hidden->setRenderEnabled(false);
        }
        else {
            $this->hidden->setValue("");
            $this->hidden->setName($this->dataInput->getName()."[0]");
        }
    }

    protected function renderItems() : void
    {
        if (!$this->iterator) {

            $this->item->setValue(1);
            $this->item->setName($this->dataInput->getName());
            $this->item->setSelected((bool)$this->dataInput->getValue());

            $this->item->render();

        }
        else {
            parent::renderItems();
        }
    }


}

?>
