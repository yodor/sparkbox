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

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "googleID_analytics", "Google Analytics ID (eg: UA-123456789-1)", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "googleID_ads", "Google Ads ID (eg: AW-123456789)", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "facebookID_pixel", "Facebook Pixel ID", 0);
        $this->addInput($field);
    }

}

?>