<?php
include_once("input/renderers/RadioField.php");
include_once("input/renderers/CheckItem.php");

class CheckField extends RadioField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setItemRenderer(new CheckItem());
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
