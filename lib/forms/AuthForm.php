<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");

class AuthForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "username", "Email", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::PASSWORD, "password", "Password", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::HIDDEN, "rand", "rand", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::HIDDEN, "pass", "pass_hash", 1);
        $this->addInput($field);

    }


}

?>