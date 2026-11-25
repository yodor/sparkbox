<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

/**
 * Contact enquiry: name, e-mail (the owner answers it), phone (optional), message
 */
class ContactRequestForm extends InputForm
{
    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXT, "fullname", "Name", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::EMAIL, "email", "Email", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "phone", "Phone", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXTAREA, "query", "Query", 1);
        $this->addInput($field);
    }
}
