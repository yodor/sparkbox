<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

class NewsItemInputForm extends InputForm
{

    public function __construct()
    {

        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "item_title", "Title", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::DATE, "item_date", "Date", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::MCE_TEXTAREA, "content", "Content", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "photo", "Photo", 1);
        $field->getProcessor()->transact_mode = InputProcessor::TRANSACT_OBJECT;
        $field->getProcessor()->max_slots = 1;
        $this->addInput($field);

    }

}

?>
