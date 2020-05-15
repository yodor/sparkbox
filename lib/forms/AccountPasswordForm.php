<?php
include_once("forms/InputForm.php");
include_once("input/DataInput.php");

class AccountPasswordForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = new DataInput("cpass", "Current Password", 0);
        new PasswordField($field);
        $this->addInput($field);

        $field = new DataInput("pass", "New Password", 0);
        new PasswordField($field);
        $this->addInput($field);

        $field = new DataInput("pass1", "Repeat Password", 0);
        new PasswordField($field);
        $this->addInput($field);

        $field = new DataInput("cpassword", "Current Password MD5", 0);
        new HiddenField($field);
        $this->addInput($field);

        $field = new DataInput("password", "Create Password MD5", 0);
        new HiddenField($field);
        $this->addInput($field);

        $field = new DataInput("password1", "Repeat Password MD5", 0);
        new HiddenField($field);
        $this->addInput($field);

        $field = new DataInput("rand", "Salt", 0);
        new HiddenField($field);
        $this->addInput($field);
    }

}

?>