<?php
include_once("input/renderers/InputField.php");

class PasswordField extends InputFieldTag
{

    protected function processInputAttributes()
    {
        parent::processInputAttributes();
        $this->setInputAttribute("type", "password");

    }

}

?>