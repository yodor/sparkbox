<?php
include_once("input/renderers/InputField.php");

class PasswordField extends InputFieldTag
{

    protected function prepareInputAttributes(): string
    {
        $this->setInputAttribute("type", "password");
        return parent::prepareInputAttributes();
    }

}

?>