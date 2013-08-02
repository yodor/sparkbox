<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");


$page = new DemoPage();


$form = new InputForm();



$field_text = new ArrayInputField("text", "Text", 0);
$field_text->allow_dynamic_addition = true;
$field_text->setRenderer(new TextField());
$field_text->setValidator(new EmptyValueValidator());
$field_text->setProcessor(new BeanPostProcessor());
$form->addField($field_text);

$f1 = new ArrayInputField("field1", "TextArea", 1);
$f1->allow_dynamic_addition = true;
$f1->setRenderer(new TextArea());
$f1->setValidator(new EmptyValueValidator());
$f1->setProcessor(new BeanPostProcessor());
$form->addField($f1);

$aw2 = new ArraySelector(array("Select Item 1", "Select Item 2", "Select Item 3"),"item_id", "item_value");

$f2 = new ArrayInputField("field2", "Select", 1);
$f2->allow_dynamic_addition = true;

$sr = new SelectField();
$sr->setSource($aw2);
$sr->list_key="item_id";
$sr->list_label="item_value";

$f2->setRenderer($sr);
$f2->setValidator(new EmptyValueValidator());
$f2->setProcessor(new BeanPostProcessor());
$form->addField($f2);


$f8 = new ArrayInputField("field8", "Date", 1);
$f8->allow_dynamic_addition = true;

$f8->setRenderer(new DateField());
$f8->setValidator(new DateValidator());
$f8->setProcessor(new DateInputProcessor());
$form->addField($f8);






$aw = new ArraySelector(array("Item1", "Item2", "Item3"), "item_id", "item_value");

$f16 = new ArrayInputField("field16", "Checkbox", 1);
$f16->allow_dynamic_addition = true;

$r16 = new CheckField();
$r16->setSource($aw);
$r16->list_key = "item_value";
$r16->list_label = "item_value";
$f16->setRenderer($r16);
$f16->setValidator(new EmptyValueValidator());
$f16->setProcessor(new BeanPostProcessor());
$form->addField($f16);




$aw3 = new ArraySelector(array("Radio Item1", "Radio Item2", "Radio Item3"),"item_id", "item_value");

$f19 = new ArrayInputField("field19", "Radio", 1);
$f19->allow_dynamic_addition = true;

$r19 = new RadioField();
$r19->setSource($aw3);
$r19->list_key = "item_value";
$r19->list_label = "item_value";
$f19->setRenderer($r19);
$f19->setValidator(new EmptyValueValidator());
$f19->setProcessor(new BeanPostProcessor());
$form->addField($f19);


$f17 = new ArrayInputField("field17", "Phone", 1);
$f17->allow_dynamic_addition = true;
$f17->add_field_text = "Add Phone";
$f17->source_label_visible = true;
$f17->append_offset = -1;
$f17->setRenderer(new PhoneField());
$f17->setValidator(new PhoneValidator());
$f17->setProcessor(new PhoneInputProcessor());
$form->addField($f17);


$f18 = new ArrayInputField("field18", "Time", 1);
$f18->allow_dynamic_addition = true;
$f18->add_field_text = "Add Time";
$f18->source_label_visible = true;
$f18->append_offset = -1;
$f18->setRenderer(new TimeField());
$f18->setValidator(new TimeValidator());
$f18->setProcessor(new TimeInputProcessor());
$form->addField($f18);

$form_render = new FormRenderer();
$form_render->setAttribute("name", "myform");
$form_render->setAttribute("id", "myform");
$form_render->setFieldLayout(FormRenderer::FIELD_HBOX);

$form->setRenderer($form_render);
$form->setProcessor(new FormProcessor());



$form->getProcessor()->processForm($form);

$page->beginPage();



$form_render->renderForm($form);

$page->finishPage();


?>