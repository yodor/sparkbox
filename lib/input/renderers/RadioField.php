<?php
include_once("input/renderers/DataIteratorField.php");
include_once("input/renderers/RadioItem.php");

class RadioField extends DataIteratorField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->setItemRenderer(new RadioItem());
    }

}

?>
