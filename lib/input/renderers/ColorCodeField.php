<?php
include_once("lib/input/renderers/InputFieldTag.php");

class ColorCodeField extends InputFieldTag
{

    public function __construct()
    {
        parent::__construct();

        $this->setFieldAttribute("type", "color");

    }


}

?>