<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");
include_once("lib/iterators/ArrayDataIterator.php");

$page = new DemoPage();

$form = new InputForm();

//Array of TextField
$textField = new ArrayDataInput("textField", "Text", 0);
$textField->allow_dynamic_addition = true;

$arf1 = new ArrayField(new TextField($textField));

$textField->setValidator(new EmptyValueValidator());
$textField->setProcessor(new BeanPostProcessor());

$form->addInput($textField);
//

//Array of TextArea
$textArea = new ArrayDataInput("textArea", "Text Area", 1);
$textArea->allow_dynamic_addition = true;

$arf2 = new ArrayField(new TextArea($textArea));

$textArea->setValidator(new EmptyValueValidator());
$textArea->setProcessor(new BeanPostProcessor());
$form->addInput($textArea);
//

//Array of SelectField
$selectField = new ArrayDataInput("selectField", "Select", 1);
$selectField->allow_dynamic_addition = true;

$select_items = new ArrayDataIterator(array("Select Item 1", "Select Item 2", "Select Item 3"));
$sr = new SelectField($selectField);
$sr->setIterator($select_items);
$sr->list_key = ArrayDataIterator::KEY_ID;
$sr->list_label = ArrayDataIterator::KEY_VALUE;

$arf3 = new ArrayField($sr);

$selectField->setValidator(new EmptyValueValidator());
$selectField->setProcessor(new BeanPostProcessor());
$form->addInput($selectField);
//

//Array of CheckField
$checkField = new ArrayDataInput("checkField", "Checkbox", 1);
$checkField->allow_dynamic_addition = true;

$check_items = new ArrayDataIterator(array("CItem1", "CItem2", "CItem3"));
$cr = new CheckField($checkField);
$cr->setIterator($check_items);
$cr->list_key = ArrayDataIterator::KEY_VALUE;
$cr->list_label = ArrayDataIterator::KEY_VALUE;

$arf4 = new ArrayField($cr);

$checkField->setValidator(new EmptyValueValidator());
//???
//$checkField->getValidator()->require_array_value = true;

$checkField->setProcessor(new BeanPostProcessor());
$form->addInput($checkField);


//Array of RadioField
$radioField = new ArrayDataInput("radioField", "Radio Button", 1);
$radioField->allow_dynamic_addition = true;

$radio_items = new ArrayDataIterator(array("RItem1", "RItem2", "RItem3"));
$rr = new RadioField($radioField);
$rr->setIterator($radio_items);
$rr->list_key = ArrayDataIterator::KEY_VALUE;
$rr->list_label = ArrayDataIterator::KEY_VALUE;

$arf5 = new ArrayField($rr);

$radioField->setValidator(new EmptyValueValidator());
$radioField->setProcessor(new BeanPostProcessor());
$form->addInput($radioField);
//



//Array of PhoneField
$phoneField = new ArrayDataInput("phoneField", "Phone", 1);
$phoneField->allow_dynamic_addition = true;
$phoneField->add_field_text = "Add Phone";
$phoneField->source_label_visible = true;
$phoneField->append_offset = -1;

//
$arf6 = new ArrayField(new PhoneField($phoneField));

$phoneField->setValidator(new PhoneValidator());
$phoneField->setProcessor(new PhoneInputProcessor());

$form->addInput($phoneField);
//

//Array of DateField
$dateField = new ArrayDataInput("dateField", "Date", 1);
$dateField->allow_dynamic_addition = true;

$arf7 = new ArrayField(new DateField($dateField));

$dateField->setValidator(new DateValidator());
$dateField->setProcessor(new DateInputProcessor());

$form->addInput($dateField);
//

//Array of TimeField
$timeInput = new ArrayDataInput("timeField", "Time", 1);
$timeInput->allow_dynamic_addition = true;
//$f18->add_field_text = "Add Time";
//$f18->source_label_visible = true;
//$f18->append_offset = -1;

$arf8 = new ArrayField(new TimeField($timeInput));

$timeInput->setValidator(new TimeValidator());
$timeInput->setProcessor(new TimeInputProcessor());
$form->addInput($timeInput);

$form_render = new FormRenderer();
$form_render->setAttribute("name", "myform");
$form_render->setAttribute("id", "myform");
$form_render->setLayout(FormRenderer::FIELD_HBOX);

$form->setRenderer($form_render);
$form->setProcessor(new FormProcessor());

$form->getProcessor()->processForm($form);

$page->startRender();

$form_render->renderForm($form);

$page->finishRender();


?>