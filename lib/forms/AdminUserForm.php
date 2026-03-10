<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

include_once("beans/AdminUsersBean.php");
include_once("beans/AdminAccessBean.php");

include_once("iterators/DBEnumIterator.php");

class AdminUserForm extends InputForm
{

    protected array $roles = array();

    public function __construct()
    {

        parent::__construct();

        //
        $field = DataInputFactory::Create(InputType::EMAIL, "email", "Email", 1);
        $this->addInput($field);

        //
        $field = DataInputFactory::Create(InputType::TEXT, "fullname", "Full Name", 0);
        $this->addInput($field);

        //
        $field = DataInputFactory::Create(InputType::PASSWORD, "password", "Create Password", 0);
        $field->getRenderer()->input()?->setAttribute("autocomplete", "off");
        $this->addInput($field);

        //
        $field = DataInputFactory::Create(InputType::PASSWORD, "password_compare", "Repeat Password", 0);
        $field->getProcessor()->skip_transaction = TRUE;
        $field->getRenderer()->input()?->setAttribute("autocomplete", "off");
        $this->addInput($field);


        //
        $field = DataInputFactory::Create(InputType::SELECT, "access_level", "Access Level", 1);

        $enum = new DBEnumIterator("admin_users", "access_level");

        $rend = $field->getRenderer();
        $rend->setDefaultOption(null);
        $rend->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_VALUE);
        $rend->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
        $rend->setIterator($enum);
        $rend->setAttribute("onChange", "toggleRoles()");

        $this->addInput($field);

        //
        $field =  DataInputFactory::Create(InputType::CHECKBOX, "role", "Menu Access Level", 0);
        $field->getProcessor()->setTransactBean(new AdminAccessBean());
        $rend = $field->getRenderer();
        $rend->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_VALUE);
        $rend->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
        $rend->setIterator(new ArrayDataIterator($this->roles));

        $this->addInput($field);


        $this->setBean(new AdminUsersBean());

    }

    //post_data already assigned
    public function validate(): void
    {
        if (strcmp($this->getInput("access_level")->getValue(), "Limited Access") === 0) {
            $this->getInput("role")->setRequired(TRUE);
        }
        else {
            $this->getInput("role")->setRequired(FALSE);
            $this->getInput("role")->setValue(array());
        }

        parent::validate();

        $password = $this->getInput("password")->getValue(); //hold the input that is rendered
        $password_compare = $this->getInput("password_compare")->getValue(); //hold the input that is rendered

        if (strcmp($password, $password_compare) !== 0) throw new Exception(tr("Passwords do not match"));

        $req_email = $this->getInput("email")->getValue();
        $existing = $this->getBean()->getResult("email", $req_email);

        if ($existing) {

            $existID = $existing[$this->getBean()->key()];

            if ($this->getEditID() != $existID) {
                $this->getInput("email")->setError("This email is already registered with other account");
            }
        }

    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
        $rend = $this->getInput("role")->getRenderer();
        if ($rend instanceof CheckField) {
            $rend->setIterator(new ArrayDataIterator($roles));
        }

    }

}