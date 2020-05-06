<?php
include_once("lib/input/renderers/InputFieldTag.php");

class TextField extends InputFieldTag
{

    protected function prepareInputAttributes(): string
    {
        $this->setInputAttribute("type", "text");
        return parent::prepareInputAttributes();
    }

}

?>