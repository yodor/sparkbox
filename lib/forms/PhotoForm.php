<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

class PhotoForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = new DataInput("caption", "Caption", 0);
        new TextField($field);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "photo", "Photo", 1);
        $this->addInput($field);

    }

}

?>