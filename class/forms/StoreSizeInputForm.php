<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/InputFactory.php");


class StoreSizeInputForm extends InputForm
{

    public function __construct()
    {

	  $field = InputFactory::CreateField(InputFactory::TEXTFIELD, "size_value", "Size Code", 1);
	  $this->addField($field);
	  $field->enableTranslator(true);

	}

}
?>
