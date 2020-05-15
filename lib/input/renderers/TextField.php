<?php
include_once("input/renderers/InputFieldTag.php");

class TextField extends InputFieldTag
{

    protected function processInputAttributes()
    {
        parent::processInputAttributes();

        $this->setInputAttribute("type", "text");

    }

}

?>