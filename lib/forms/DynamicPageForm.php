<?php
include_once("forms/InputForm.php");
include_once("iterators/ArrayDataIterator.php");

class DynamicPageForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = DataInputFactory::Create(InputType::TEXT,"item_title", "Title", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::MCE_TEXTAREA, "content", "Content", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "keywords", "Menu Group (separate with ';')", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::DATE,"item_date", "Date", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::CHECKBOX, "visible", "Visible", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::SELECT, "render_class", "CSS Class Name", 0);
        //
        $sel = new ArrayDataIterator(array("Normal", "Notices"));
        //
        $rend = $field->getRenderer();
        $rend->setIterator($sel);
        //
        $rend->setDefaultOption(null);

        $rend->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_ID);
        $rend->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
        //
        //

        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::SESSION_IMAGE, "photo", "Photo", 0);
        $this->addInput($field);


    }

}