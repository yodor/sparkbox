<?php
include_once("forms/InputForm.php");
include_once("objects/data/GTAGObject.php");
include_once("input/processors/DataObjectInput.php");

class SEOConfigForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXTAREA, "meta_description", "Meta Description", 0);
        $rend = $field->getRenderer();
        $rend->input()?->setAttribute("rows", 10);
        $rend->input()?->setAttribute("cols", 80);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "googleID_analytics", "Google Analytics ID (eg: UA-123456789-1)", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "googleID_ads", "Google Ads ID (eg: AW-123456789)", 0);
        $this->addInput($field);

        $field = new DataInput("googleID_ads_conversion", "Google Ads Any Page Conversion ID", 0);
        $obj = new GTAGObject();
        $obj->setCommand(GTAGObject::COMMAND_EVENT);
        $obj->setType("conversion");
        $obj->setParamTemplate("{'send_to': '%googleID_ads_conversion%'}");
        new DataObjectInput($field, $obj);
        new TextField($field);
        $this->addInput($field);


        $field = DataInputFactory::Create(InputType::TEXT, "facebookID_pixel", "Facebook Pixel ID", 0);
        $this->addInput($field);
    }

}