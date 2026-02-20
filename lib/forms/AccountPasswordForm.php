<?php
include_once("forms/InputForm.php");

class AccountPasswordForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(InputType::PASSWORD, "cpass", "Current Password", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::PASSWORD, "pass", "New Password", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::PASSWORD, "pass1", "Repeat Password", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::HIDDEN, "cpassword", "Current Password MD5", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::HIDDEN,"password", "Create Password MD5", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::HIDDEN,"password1", "Repeat Password MD5", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::HIDDEN,"rand", "Salt", 0);
        $this->addInput($field);
    }

}