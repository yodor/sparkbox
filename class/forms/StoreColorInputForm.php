<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");


class StoreColorInputForm extends InputForm
{

    public function __construct()
    {

        $field = DataInputFactory::Create(DataInputFactory::TEXTFIELD, "color", "Color Name", 1);
        $this->addField($field);
        $field->enableTranslator(true);

        $field = DataInputFactory::Create(DataInputFactory::COLORCODE, "color_code", "Color Code", 0);

        $this->addField($field);

    }

}

?>
