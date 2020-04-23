<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInput.php");
include_once("lib/input/validators/EmailValidator.php");

class UserInputForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = new DataInput("first_name", "First Name", 1);
        $field->setRenderer(new TextField());
        $this->addField($field);

        $field = new DataInput("last_name", "Last Name", 1);
        $field->setRenderer(new TextField());
        $this->addField($field);

        $field = new DataInput("email", "Email", 1);
        $field->setRenderer(new TextField());
        $field->setValidator(new EmailValidator());
        $this->addField($field);


        $field = new DataInput("pass", "Create Password", 0);
        $field->setRenderer(new PasswordField());
        $field->setScriptRequired(true);
        $field->getRenderer()->setAttribute("autocomplete", "off");
        $this->addField($field);


        $field = new DataInput("pass1", "Repeat Password", 0);
        $field->setRenderer(new PasswordField());
        $field->setScriptRequired(true);
        $field->getRenderer()->setAttribute("autocomplete", "off");
        $this->addField($field);


        $field = new DataInput("pass_hash", "Password Hash", 1);
        $field->setRenderer(new HiddenField());
        $this->addField($field);


    }

    /**
     * post_data already assigned
     * @throws Exception
     */
    public function validate()
    {

        parent::validate();

        $password_hash = $this->getField("password_hash"); //hold md5 input

        $f_pass = $this->getField("pass"); //hold the input that is rendered
        $f_pass1 = $this->getField("pass1"); //hold the input that is rendered

        if (isEmptyPassword($password_hash->getValue()) === true) {
            if ($this->getEditID() > 0) {
                $password_hash->skip_transaction = true;

            }
            else {
                $f_pass->setError("Empty password");
                $f_pass1->setError("Empty password");
            }
        }
        else {

            if (strlen($password_hash->getValue()) != 32) {
                $f_pass->setError("Password length");
                $f_pass1->setError("Password length");
            }
        }

        $req_email = $this->getField("email")->getValue();
        $existing = $this->getBean()->findFieldValue("email", $req_email);

        if ($existing) {

            $existID = $existing[$this->getBean()->key()];

            if ($this->getEditID() != $existID) {
                $this->getField("email")->setError("This email is already registered with other account");
            }
        }

    }
}

?>