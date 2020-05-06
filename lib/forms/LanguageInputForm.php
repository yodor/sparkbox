<?php
include_once("forms/InputForm.php");
include_once("input/transactors/CustomFieldTransactor.php");

class LanguageInputForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = new DataInput("lang_code", "Language Code", 1);
        $field->setRenderer(new TextField());
        $this->addInput($field);

        $field = new DataInput("language", "Language Name", 1);
        $field->setRenderer(new TextField());
        // 	$field->setValueTransactor(new CustomFieldTransactor("language"));
        $this->addInput($field);

    }

}

?>