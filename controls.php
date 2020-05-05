<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");
include_once("lib/forms/InputForm.php");
include_once("lib/iterators/ArrayDataIterator.php");

$page = new DemoPage();

$form = new InputForm();

$f1 = new DataInput("field1", "Text", 1);
$tf = new TextField($f1);
$form->addInput($f1);

$f2 = new DataInput("field2", "Email", 1);
$tf1 = new TextField($f2);
$f2->setValidator(new EmailValidator());
$form->addInput($f2);
//
$f3 = new DataInput("field3", "Password", 1);
$pf = new PasswordField($f3);
$f3->setValidator(new PasswordValidator());
$form->addInput($f3);

$aw2 = new ArrayDataIterator(array("SelectItem1", "SelectItem2", "SelectItem3"));

$f4 = new DataInput("field4", "Select", 1);
$scmp = new SelectField($f4);
$scmp->setIterator($aw2);
$scmp->list_key = ArrayDataIterator::KEY_ID;
$scmp->list_label = ArrayDataIterator::KEY_VALUE;

$form->addInput($f4);

$aw3 = new ArrayDataIterator(array("SelectMultiItem1", "SelectMultiItem2", "SelectMultiItem3"));

$f4m = new DataInput("field4m", "Select Multi", 1);
$scmp1 = new SelectMultipleField($f4m);
$scmp1->setIterator($aw3);
$scmp1->list_key = ArrayDataIterator::KEY_ID;
$scmp1->list_label = ArrayDataIterator::KEY_VALUE;

$form->addInput($f4m);

$f5 = new DataInput("field5", "Text Area", 1);
$ta = new TextArea($f5);
$form->addInput($f5);

$f6 = new DataInput("field10", "Checkbox Single", 0);
$cf = new CheckField($f6);
$f6->setValidator(new EmptyValueValidator());
$f6->setProcessor(new BeanPostProcessor());
$form->addInput($f6);

$f6 = new DataInput("field101", "Accept Check", 1);
$cf1 = new CheckField($f6);
$cf1->setCaption("Accept By Clicking Here");
$f6->setValidator(new EmptyValueValidator());
$f6->setProcessor(new BeanPostProcessor());
$form->addInput($f6);

$aw = new ArrayDataIterator(array("CheckboxItem1", "CheckboxItem2", "CheckboxItem3"));

$f11 = new DataInput("field11", "Checkbox DataSource", 1);

$cf2 = new CheckField($f11);
$cf2->setIterator($aw);
$cf2->list_key = ArrayDataIterator::KEY_VALUE;
$cf2->list_label = ArrayDataIterator::KEY_VALUE;

$f11->setValidator(new EmptyValueValidator());
$f11->setProcessor(new BeanPostProcessor());
$form->addInput($f11);

$aw = new ArrayDataIterator(array("CheckboxItem1", "CheckboxItem2", "CheckboxItem3"));

$f11 = new DataInput("field11_req", "Checkbox DataSource<BR><small>Require array value</small>", 1);

$cf3 = new CheckField($f11);
$cf3->setIterator($aw);
$cf3->list_key = ArrayDataIterator::KEY_VALUE;
$cf3->list_label = ArrayDataIterator::KEY_VALUE;

$validator = new EmptyValueValidator();
$validator->require_array_value = TRUE;
$f11->setValidator($validator);
$f11->setProcessor(new BeanPostProcessor());
$form->addInput($f11);

$aw1 = new ArrayDataIterator(array("RadioItem1", "RadioItem2", "RadioItem3"));
$f12 = new DataInput("field12", "Radiobox DataSource", 1);
$rf = new RadioField($f12);
$rf->setIterator($aw1);
$rf->list_key = ArrayDataIterator::KEY_VALUE;
$rf->list_label = ArrayDataIterator::KEY_VALUE;

$f12->setValidator(new EmptyValueValidator());
$f12->setProcessor(new BeanPostProcessor());
$form->addInput($f12);

$f7 = new DataInput("field7", "Date", 1);
$df = new DateField($f7);
$f7->setValidator(new DateValidator());
$f7->setProcessor(new DateInputProcessor());
$form->addInput($f7);

$f8 = new DataInput("field8", "Time", 1);
$tf = new TimeField($f8);
$f8->setValidator(new TimeValidator());
$f8->setProcessor(new TimeInputProcessor());
$form->addInput($f8);

$f9 = new DataInput("field9", "Phone", 1);
$pf = new PhoneField($f9);
$f9->setValidator(new PhoneValidator());
$f9->setProcessor(new PhoneInputProcessor());
$form->addInput($f9);

$f15 = new DataInput("field15", "Hidden", 0);
$hf = new HiddenField($f15);
$form->addInput($f15);

$f16 = DataInputFactory::Create(DataInputFactory::CAPTCHA, "captcha_field", "Captcha Code", 1);
$form->addInput($f16);

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
