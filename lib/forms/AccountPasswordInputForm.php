<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInput.php");


class AccountPasswordInputForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = new DataInput("cpass", "Current Password", 0);
        $field->setRenderer(new PasswordField());
        $this->addInput($field);

        $field = new DataInput("pass", "New Password", 0);
        $field->setRenderer(new PasswordField());
        $this->addInput($field);

        $field = new DataInput("pass1", "Repeat Password", 0);
        $field->setRenderer(new PasswordField());
        $this->addInput($field);

        $field = new DataInput("cpassword", "Current Password MD5", 0);
        $field->setRenderer(new HiddenField());
        $this->addInput($field);

        $field = new DataInput("password", "Create Password MD5", 0);
        $field->setRenderer(new HiddenField());
        $this->addInput($field);

        $field = new DataInput("password1", "Repeat Password MD5", 0);
        $field->setRenderer(new HiddenField());
        $this->addInput($field);

        $field = new DataInput("rand", "Salt", 0);
        $field->setRenderer(new HiddenField());
        $this->addInput($field);
    }

}

?>