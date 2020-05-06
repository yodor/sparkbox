<?php
include_once("lib/input/renderers/InputFieldTag.php");

class ColorCodeField extends InputFieldTag
{

    protected function prepareInputAttributes(): string
    {
        $this->setInputAttribute("type", "color");
        return parent::prepareInputAttributes();
    }
}

?>