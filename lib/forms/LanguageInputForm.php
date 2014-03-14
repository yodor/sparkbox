<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/transactors/CustomFieldTransactor.php");

class LanguageInputForm extends InputForm
{

    public function __construct()
    {

	$field = new InputField("lang_code","Language Code",1);
	$field->setRenderer(new TextField());
	$this->addField($field);

	$field = new InputField("language_name", "Language Name", 1);
	$field->setRenderer(new TextField());
	$field->setValueTransactor(new CustomFieldTransactor("language"));
	$this->addField($field);

    }

}
?>