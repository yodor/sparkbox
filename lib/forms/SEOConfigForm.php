<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");

/**
 * The SEO settings of the site (config section 'seo'): the description of the pages that have none of their own
 */
class SEOConfigForm extends InputForm
{
    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXTAREA, "meta_description", "Default Meta Description (up to 150 characters)", 0);
        $field->getRenderer()->input()?->setAttribute("rows", "4");
        $field->getRenderer()->input()?->setAttribute("cols", "80");
        $this->addInput($field);
    }
}
