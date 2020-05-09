<?php
include_once("forms/InputForm.php");
include_once("iterators/ArrayDataIterator.php");

class DynamicPageInputForm extends InputForm
{

    public function __construct()
    {
        parent::__construct();

        $field = new DataInput("item_title", "Title", 1);
        new TextField($field);
        $this->addInput($field);

        $field = new DataInput("content", "Content", 1);
        $rend = new MCETextArea($field);
        $rend->setAttribute("rows", 20);
        $rend->setAttribute("cols", 80);
        $this->addInput($field);

        $field = new DataInput("item_date", "Date", 0);
        new DateField($field);
        $field->setValidator(new DateValidator());
        $field->setProcessor(new DateInputProcessor());
        $this->addInput($field);

        $field = new DataInput("visible", "Visible", 0);
        new CheckField($field);
        $this->addInput($field);

        $field = new DataInput("render_class", "CSS Class Name", 0);
        //
        $sel = new ArrayDataIterator(array("Notices"));
        //
        $rend = new SelectField($field);
        $rend->setItemIterator($sel);
        //
        $rend->na_label = "Normal";
        $rend->na_value = "";
        $rend->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_ID);
        $rend->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
        //
        //

        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "photo", "Photo", 0);
        $field->transact_mode = DataInput::TRANSACT_OBJECT;
        $field->getProcessor()->max_slots = 1;
        $this->addInput($field);

        $this->getInput("item_title")->enableTranslator(TRUE);
        $this->getInput("content")->enableTranslator(TRUE);
    }

}

?>
