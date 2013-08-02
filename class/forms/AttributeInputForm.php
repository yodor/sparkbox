<?php
include_once ("lib/forms/InputForm.php");


class AttributeInputForm extends InputForm
{

    public function __construct()
    {
	$field = new InputField("name", "Attribute Name", 1);
	$field->setRenderer(new TextField());
	$this->addField($field);

	$field = new InputField("unit", "Attribute Unit", 1);
	$field->setRenderer(new TextField());
	$this->addField($field);
    }

}
?>