<?php
include_once("forms/InputForm.php");

class NewsItemInputForm extends InputForm
{

    public function __construct()
    {

        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXT, "item_title", "Title", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::DATE, "item_date", "Date", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::MCE_TEXTAREA, "content", "Content", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::SESSION_IMAGE, "photo", "Photo", 1);
        $this->addInput($field);

    }

}

?>