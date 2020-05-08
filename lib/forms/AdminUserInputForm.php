<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

include_once("beans/AdminUsersBean.php");
include_once("beans/AdminAccessBean.php");

include_once("iterators/AdminRolesIterator.php");
include_once("iterators/DBEnumIterator.php");

include_once("input/transactors/CustomFieldTransactor.php");

class AdminUserInputForm extends InputForm
{

    public function __construct()
    {

        parent::__construct();

        $field = new DataInput("email", "Email", 1);
        new TextField($field);
        $field->setValidator(new EmailValidator());
        $this->addInput($field);

        $field = new DataInput("fullname", "Full Name", 0);
        new TextField($field);
        $this->addInput($field);

        $field = new DataInput("pass", "Create Password", 0);
        $field->skip_transaction = true;
        $rend = new PasswordField($field);
        $rend->setAttribute("autocomplete", "off");
        $this->addInput($field);

        $field = new DataInput("pass1", "Repeat Password", 0);
        $field->skip_transaction = true;
        $rend = new PasswordField($field);
        $rend->setAttribute("autocomplete", "off");
        $this->addInput($field);

        $field = new DataInput("password_hash", "Password Hash", 1);
        new HiddenField($field);

        //transact this field to DB field password
        $field->setValueTransactor(new CustomFieldTransactor("password"));
        $this->addInput($field);


        $field = new DataInput("access_level", "Access Level", 1);

        $enum = new DBEnumIterator("admin_users", "access_level");

        $rend = new SelectField($field);
        $rend->na_str = "";
        $rend->na_val = false;
        $rend->list_key = ArrayDataIterator::KEY_VALUE;
        $rend->list_label = ArrayDataIterator::KEY_VALUE;
        $rend->setIterator($enum);
        $rend->setAttribute("onChange", "toggleRoles()");

        $this->addInput($field);


        $field = new DataInput("role", "Admin Roles", 0);
        $field->setSource(new AdminAccessBean());

        $rend = new CheckField($field);
        $rend->setIterator(new AdminRolesIterator());
        $rend->list_key = ArrayDataIterator::KEY_ID;
        $rend->list_label = ArrayDataIterator::KEY_VALUE;

        // 	  $field->setValueTransactor(new AdminRolesTransactor());

        $this->addInput($field);


    }


    //post_data already assigned
    public function validate()
    {
        if (strcmp($this->getInput("access_level")->getValue(), "Limited Access") == 0) {
            $this->getInput("role")->setRequired(true);
        }
        else {
            $this->getInput("role")->setRequired(false);
            $this->getInput("role")->setValue(array());
        }

        parent::validate();

        $password_hash = $this->getInput("password_hash"); //hold md5 input

        $f_pass = $this->getInput("pass"); //hold the input that is rendered
        $f_pass1 = $this->getInput("pass1"); //hold the input that is rendered

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

        $req_email = $this->getInput("email")->getValue();
        $existing = $this->getBean()->findFieldValue("email", $req_email);

        if ($existing) {

            $existID = $existing[$this->getBean()->key()];

            if ($this->getEditID() != $existID) {
                $this->getInput("email")->setError("This email is already registered with other account");
            }
        }

    }


}

?>