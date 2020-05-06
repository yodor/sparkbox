<?php
include_once("input/renderers/InputFieldTag.php");

class HiddenField extends InputFieldTag
{

    protected function prepareInputAttributes() : string
    {
        $this->setInputAttribute("type", "hidden");
        return parent::prepareInputAttributes();
    }

}

?>