<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");


class FAQSectionInputForm extends InputForm
{

    public function __construct()
    {

        parent::__construct();


        $field = DataInputFactory::Create(InputType::TEXT, "section_name", "Section", 1);
        $this->addInput($field);


    }

}

?>