<?php
include_once("lib/forms/InputForm.php");


class SEOConfigForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::TEXTAREA, "meta_description", "Meta Description", 0);
        $rend = $field->getRenderer();
        $rend->setAttribute("rows", 10);
        $rend->setAttribute("cols", 80);
        $this->addInput($field);


        $field = DataInputFactory::Create(DataInputFactory::TEXTAREA, "meta_keywords", "Meta Keywords", 0);
        $rend = $field->getRenderer();
        $rend->setAttribute("rows", 10);
        $rend->setAttribute("cols", 80);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXTAREA, "google_analytics", "Google Analytics", 0);
        $rend = $field->getRenderer();
        $rend->setAttribute("rows", 10);
        $rend->setAttribute("cols", 80);
        $this->addInput($field);

    }


}

?>
