<?php
include_once("lib/input/renderers/InputFieldTag.php");

class TextField extends InputFieldTag
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->setFieldAttribute("type", "text");

    }


}

?>