<?php
include_once("forms/InputForm.php");


class SEOConfigForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::TEXTAREA, "meta_description", "Meta Description", 0);
        $rend = $field->getRenderer();
        $rend->setInputAttribute("rows", 10);
        $rend->setInputAttribute("cols", 80);
        $this->addInput($field);


        $field = DataInputFactory::Create(DataInputFactory::TEXTAREA, "meta_keywords", "Meta Keywords", 0);
        $rend = $field->getRenderer();
        $rend->setInputAttribute("rows", 10);
        $rend->setInputAttribute("cols", 80);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXTAREA, "google_analytics", "Google Analytics", 0);
        $rend = $field->getRenderer();
        $rend->setInputAttribute("rows", 10);
        $rend->setInputAttribute("cols", 80);
        $this->addInput($field);

    }


}

?>
