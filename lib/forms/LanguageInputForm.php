<?php
include_once("forms/InputForm.php");

class LanguageInputForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = new DataInput("lang_code", "Language Code", 1);
        new TextField($field);
        $this->addInput($field);

        $field = new DataInput("language", "Language Name", 1);
        new TextField($field);
        $this->addInput($field);

    }

}

?>