<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

class LoginForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "email", "Email", 1);
        $field->getRenderer()->setInputAttribute("autocomplete", "on");
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::PASSWORD, "password", "Password", 1);
        $field->getRenderer()->setInputAttribute("autocomplete", "on");
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::HIDDEN, "rand", "rand", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::HIDDEN, "pass", "pass_hash", 1);
        $this->addInput($field);

    }


}

?>