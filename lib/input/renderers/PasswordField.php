<?php
include_once("input/renderers/InputField.php");

class PasswordField extends InputFieldTag
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->input->setType("password");
    }

}

?>