<?php
include_once("input/renderers/InputFieldTag.php");

class ColorCodeField extends InputFieldTag
{

    protected function processInputAttributes()
    {
        parent::processInputAttributes();
        $this->setInputAttribute("type", "color");

    }
}

?>