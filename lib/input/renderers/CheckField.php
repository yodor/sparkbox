<?php
include_once("input/renderers/DataIteratorField.php");
include_once("input/renderers/CheckItem.php");

class CheckField extends DataIteratorField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setItemRenderer(new CheckItem());
    }

    protected function renderItems() : void
    {
        if (!$this->iterator) {

            $item = clone $this->item;

            $item->setValue(1);
            $item->setName($this->dataInput->getName());
            $item->setSelected((bool)$this->dataInput->getValue());

            $item->render();
        }
        else {
            parent::renderItems();
        }
    }


}

?>
