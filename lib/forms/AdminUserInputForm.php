<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");

include_once("lib/beans/AdminUsersBean.php");
include_once("lib/beans/AdminAccessBean.php");

include_once("lib/selectors/AdminRolesSelector.php");
include_once("lib/selectors/DBEnumSelector.php");

include_once("lib/input/transactors/CustomFieldTransactor.php");

class AdminUserInputForm extends InputForm
{

    public function __construct()
    {

        parent::__construct();

        $field = new DataInput("email", "Email", 1);
        $field->setRenderer(new TextField());
        $field->setValidator(new EmailValidator());
        $this->addField($field);

        $field = new DataInput("fullname", "Full Name", 0);
        $field->setRenderer(new TextField());
        $this->addField($field);

        $field = new DataInput("pass", "Create Password", 0);
        $field->skip_transaction = true;
        $rend = new PasswordField();
        $rend->setAttribute("autocomplete", "off");
        $field->setRenderer($rend);
        $this->addField($field);

        $field = new DataInput("pass1", "Repeat Password", 0);
        $field->skip_transaction = true;
        $rend = new PasswordField();
        $rend->setAttribute("autocomplete", "off");
        $field->setRenderer($rend);
        $this->addField($field);

        $field = new DataInput("password_hash", "Password Hash", 1);
        $field->setRenderer(new HiddenField());

        //transact this field to DB field password
        $field->setValueTransactor(new CustomFieldTransactor("password"));
        $this->addField($field);


        $field = new DataInput("access_level", "Access Level", 1);

        $enum = new DBEnumSelector("admin_users", "access_level");

        $rend = new SelectField();
        $rend->na_str = "";
        $rend->na_val = false;
        $rend->list_key = "access_level";
        $rend->list_label = "access_level";
        $rend->setSource($enum);
        $rend->setAttribute("onChange", "toggleRoles()");
        $field->setRenderer($rend);

        $this->addField($field);


        $field = new DataInput("role", "Admin Roles", 0);
        $field->setSource(new AdminAccessBean());

        $rend = new CheckField();
        $rend->setSource(new AdminRolesSelector());
        $rend->list_key = "roles";
        $rend->list_label = "roles";
        $field->setRenderer($rend);
        // 	  $field->setValueTransactor(new AdminRolesTransactor());

        $this->addField($field);


    }


    //post_data already assigned
    public function validate()
    {
        if (strcmp($this->getField("access_level")->getValue(), "Limited Access") == 0) {
            $this->getField("role")->setRequired(true);
        }
        else {
            $this->getField("role")->setRequired(false);
            $this->getField("role")->setValue(array());
        }

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