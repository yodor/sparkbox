<?php
include_once("forms/InputForm.php");
include_once("input/DataInput.php");
include_once("input/validators/EmailValidator.php");

class UserInputForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "first_name", "First Name", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "last_name", "Last Name", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::EMAIL, "email", "Email", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::PASSWORD, "pass", "Create Password", 0);
        $field->getRenderer()->setAttribute("autocomplete", "off");
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::PASSWORD, "pass1", "Repeat Password", 0);
        $field->getRenderer()->setAttribute("autocomplete", "off");
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::HIDDEN, "pass_hash", "Password Hash", 1);
        $this->addInput($field);

    }

    /**
     * post_data already assigned
     * @throws Exception
     */
    public function validate()
    {

        parent::validate();

        $password_hash = $this->getInput("password_hash"); //hold md5 input

        $f_pass = $this->getInput("pass"); //hold the input that is rendered
        $f_pass1 = $this->getInput("pass1"); //hold the input that is rendered

        if (isEmptyPassword($password_hash->getValue()) === TRUE) {
            if ($this->getEditID() > 0) {
                $password_hash->getProcessor()->skip_transaction = TRUE;

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

        $req_email = $this->getInput("email")->getValue();
        $existing = $this->getBean()->getResult("email", $req_email);

        if ($existing) {

            $existID = $existing[$this->getBean()->key()];

            if ($this->getEditID() != $existID) {
                $this->getInput("email")->setError("This email is already registered with other account");
            }
        }

    }
}

?>