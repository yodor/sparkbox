<?php
include_once("input/renderers/InputFieldTag.php");

class HiddenField extends InputFieldTag
{

    protected function processInputAttributes()
    {
        parent::processInputAttributes();
        $this->setInputAttribute("type", "hidden");

    }

}

?>