<?php
include_once ("lib/forms/InputForm.php");

class LanguageInputForm extends InputForm
{

    public function __construct()
    {

	$field = new InputField("lang_code","Language Code",1);
	$field->setRenderer(new TextField());
	$this->addField($field);

	$field = new InputField("language", "Language Name", 0);
	$field->setRenderer(new TextField());
	$this->addField($field);

    }

}
?>