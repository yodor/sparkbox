<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");


class StoreSizeInputForm extends InputForm
{

    public function __construct()
    {

        $field = DataInputFactory::Create(DataInputFactory::TEXTFIELD, "size_value", "Size Code", 1);
        $this->addField($field);
        $field->enableTranslator(true);

    }

}

?>
