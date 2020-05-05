<?php
include_once("lib/input/renderers/InputField.php");

class PasswordField extends InputFieldTag
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

//        $this->setClassName("PasswordField");

        $this->setFieldAttribute("type", "password");

    }

}

?>