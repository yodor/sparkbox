<?php
include_once("lib/input/renderers/InputField.php");

class PasswordField extends InputFieldTag
{

    public function __construct()
    {
        parent::__construct();

//        $this->setClassName("PasswordField");

        $this->setFieldAttribute("type", "password");

    }

}

?>