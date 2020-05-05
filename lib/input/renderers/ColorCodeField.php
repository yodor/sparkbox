<?php
include_once("lib/input/renderers/InputFieldTag.php");

class ColorCodeField extends InputFieldTag
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->setFieldAttribute("type", "color");

    }


}

?>