<?php
include_once("lib/forms/InputForm.php");


class AttributeInputForm extends InputForm
{

    public function __construct()
    {
        $field = new DataInput("name", "Attribute Name", 1);
        $field->setRenderer(new TextField());
        $this->addField($field);

        $field = new DataInput("unit", "Attribute Unit", 0);
        $field->setRenderer(new TextField());
        $this->addField($field);
    }

}

?>