<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

class LoginForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXT, "email", tr("Email"), 1);
        $field->getRenderer()->input()?->setAttribute("autocomplete", "on");
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::PASSWORD, "password", tr("Password"), 1);
        $field->getRenderer()->input()?->setAttribute("autocomplete", "on");
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::HIDDEN, "rand", "rand", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::HIDDEN, "pass", "pass_hash", 1);
        $this->addInput($field);

    }

}