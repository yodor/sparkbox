<?php
include_once("forms/InputForm.php");

class LanguageForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXT,"lang_code", "Language Code", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT,"language", "Language Name", 1);
        $this->addInput($field);

    }

}