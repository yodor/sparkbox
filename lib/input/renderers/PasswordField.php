<?php
include_once("input/renderers/InputFieldTag.php");

/**
 * A password input - its value is never written into the page (a stored password or secret stays on the server)
 */
class PasswordField extends InputFieldTag
{
    protected array $skip_value_types = array("file", "password");

    public function __construct(DataInput $dataInput)
    {
        parent::__construct($dataInput);
        $this->input->setType("password");
    }

}