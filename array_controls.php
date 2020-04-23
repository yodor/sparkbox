<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");

$page = new DemoPage();

$form = new InputForm();

$textField = new ArrayDataInput("textField", "Text", 0);
$textField->allow_dynamic_addition = true;
$textField->setRenderer(new TextField());
$textField->setValidator(new EmptyValueValidator());
$textField->setProcessor(new BeanPostProcessor());
$form->addField($textField);

$textArea = new ArrayDataInput("textArea", "Text Area", 1);
$textArea->allow_dynamic_addition = true;
$textArea->setRenderer(new TextArea());
$textArea->setValidator(new EmptyValueValidator());
$textArea->setProcessor(new BeanPostProcessor());
$form->addField($textArea);


$select_items = new ArraySelector(array("Select Item 1", "Select Item 2", "Select Item 3"), "item_id", "item_value");

$selectField = new ArrayDataInput("selectField", "Select", 1);
$selectField->allow_dynamic_addition = true;

$sr = new SelectField();
$sr->setSource($select_items);
$sr->list_key = "item_id";
$sr->list_label = "item_value";

$selectField->setRenderer($sr);
$selectField->setValidator(new EmptyValueValidator());
$selectField->setProcessor(new BeanPostProcessor());
$form->addField($selectField);


$dateField = new ArrayDataInput("dateField", "Date", 1);
$dateField->allow_dynamic_addition = true;

$dateField->setRenderer(new DateField());
$dateField->setValidator(new DateValidator());
$dateField->setProcessor(new DateInputProcessor());
$form->addField($dateField);


$check_items = new ArraySelector(array("Item1", "Item2", "Item3"), "item_id", "item_value");

$checkField = new ArrayDataInput("checkField", "Checkbox", 1);
$checkField->allow_dynamic_addition = true;

$cr = new CheckField();
$cr->setSource($check_items);
$cr->list_key = "item_value";
$cr->list_label = "item_value";

$checkField->setRenderer($cr);
$checkField->setValidator(new EmptyValueValidator());
//???
//$checkField->getValidator()->require_array_value = true;

$checkField->setProcessor(new BeanPostProcessor());
$form->addField($checkField);


$radio_items = new ArraySelector(array("Radio Item1", "Radio Item2", "Radio Item3"), "item_id", "item_value");

$radioField = new ArrayDataInput("radioField", "Radio Button", 1);
$radioField->allow_dynamic_addition = true;

$rr = new RadioField();
$rr->setSource($radio_items);
$rr->list_key = "item_value";
$rr->list_label = "item_value";

$radioField->setRenderer($rr);
$radioField->setValidator(new EmptyValueValidator());
$radioField->setProcessor(new BeanPostProcessor());
$form->addField($radioField);


$phoneField = new ArrayDataInput("phoneField", "Phone", 1);
$phoneField->allow_dynamic_addition = true;
$phoneField->add_field_text = "Add Phone";
$phoneField->source_label_visible = true;
$phoneField->append_offset = -1;

$phoneField->setRenderer(new PhoneField());
$phoneField->setValidator(new PhoneValidator());
$phoneField->setProcessor(new PhoneInputProcessor());
$form->addField($phoneField);


$timeField = new ArrayDataInput("timeField", "Time", 1);
$timeField->allow_dynamic_addition = true;
//$f18->add_field_text = "Add Time";
//$f18->source_label_visible = true;
//$f18->append_offset = -1;
$timeField->setRenderer(new TimeField());
$timeField->setValidator(new TimeValidator());
$timeField->setProcessor(new TimeInputProcessor());
$form->addField($timeField);

$form_render = new FormRenderer();
$form_render->setAttribute("name", "myform");
$form_render->setAttribute("id", "myform");
$form_render->setFieldLayout(FormRenderer::FIELD_HBOX);

$form->setRenderer($form_render);
$form->setProcessor(new FormProcessor());


$form->getProcessor()->processForm($form);

$page->startRender();


$form_render->renderForm($form);

$page->finishRender();


?>