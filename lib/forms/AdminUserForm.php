<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

include_once("beans/AdminUsersBean.php");
include_once("beans/AdminAccessBean.php");

include_once("iterators/AdminRolesIterator.php");
include_once("iterators/DBEnumIterator.php");

class AdminUserForm extends InputForm
{

    public function __construct()
    {

        parent::__construct();

        //
        $field = DataInputFactory::Create(DataInputFactory::EMAIL, "email", "Email", 1);
        $this->addInput($field);

        //
        $field = DataInputFactory::Create(DataInputFactory::TEXT, "fullname", "Full Name", 0);
        $this->addInput($field);

        //
        $field = DataInputFactory::Create(DataInputFactory::PASSWORD, "pass", "Create Password", 0);
        $field->getProcessor()->skip_transaction = TRUE;
        $field->getRenderer()->setInputAttribute("autocomplete", "off");
        $this->addInput($field);

        //
        $field = DataInputFactory::Create(DataInputFactory::PASSWORD, "pass1", "Repeat Password", 0);
        $field->getProcessor()->skip_transaction = TRUE;
        $field->getRenderer()->setInputAttribute("autocomplete", "off");
        $this->addInput($field);

        //
        $field = DataInputFactory::Create(DataInputFactory::HIDDEN, "password_hash", "Password Hash", 1);
        $field->getProcessor()->transact_field_name = "password";
        $this->addInput($field);

        //
        $field = new DataInput("access_level", "Access Level", 1);

        $enum = new DBEnumIterator("admin_users", "access_level");

        $rend = new SelectField($field);
        $rend->na_label = "";
        $rend->na_value = FALSE;
        $rend->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_VALUE);
        $rend->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
        $rend->setIterator($enum);
        $rend->setAttribute("onChange", "toggleRoles()");

        $this->addInput($field);

        //
        $field = new DataInput("role", "Admin Roles", 0);
        $field->getProcessor()->setTransactBean(new AdminAccessBean());

        $rend = new CheckField($field);
        $rend->setIterator(new AdminRolesIterator());
        $rend->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_ID);
        $rend->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);

        $this->addInput($field);

    }

    //post_data already assigned
    public function validate()
    {
        if (strcmp($this->getInput("access_level")->getValue(), "Limited Access") == 0) {
            $this->getInput("role")->setRequired(TRUE);
        }
        else {
            $this->getInput("role")->setRequired(FALSE);
            $this->getInput("role")->setValue(array());
        }

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