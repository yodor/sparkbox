<?php
include_once("forms/InputForm.php");
include_once("iterators/ArrayDataIterator.php");

class DynamicPageForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = new DataInput("item_title", "Title", 1);
        $field->enableTranslator(true);
        new TextField($field);
        $this->addInput($field);

        $field = new DataInput("content", "Content", 1);
        $field->enableTranslator(true);
        new MCETextArea($field);

        $this->addInput($field);

        $field = new DataInput("keywords", "Keywords", 0);
        new TextField($field);
        $this->addInput($field);

        $field = new DataInput("item_date", "Date", 0);
        new DateField($field);
        $field->setValidator(new DateValidator());

        $this->addInput($field);

        $field = new DataInput("visible", "Visible", 0);
        new CheckField($field);
        $this->addInput($field);

        $field = new DataInput("render_class", "CSS Class Name", 0);
        //
        $sel = new ArrayDataIterator(array("Normal", "Notices"));
        //
        $rend = new SelectField($field);
        $rend->setIterator($sel);
        //
        $rend->setDefaultOption(null);

        $rend->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_ID);
        $rend->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
        //
        //

        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "photo", "Photo", 0);
        $this->addInput($field);


    }

}

?>