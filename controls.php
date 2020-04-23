<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");
include_once("lib/forms/InputForm.php");

$page = new DemoPage();


$form = new InputForm();


$f1 = new DataInput("field1", "Text", 1);
$f1->setRenderer(new TextField());
$form->addField($f1);

$f2 = new DataInput("field2", "Email", 1);
$f2->setRenderer(new TextField());
$f2->setValidator(new EmailValidator());
$form->addField($f2);
//
$f3 = new DataInput("field3", "Password", 1);
$f3->setRenderer(new PasswordField());
$f3->setValidator(new PasswordValidator());
$form->addField($f3);


$aw2 = new ArraySelector(array("SelectItem1", "SelectItem2", "SelectItem3"), "item_id", "item_value");

$f4 = new DataInput("field4", "Select", 1);
$scmp = new SelectField();
$scmp->setSource($aw2);
$scmp->list_key = "item_id";
$scmp->list_label = "item_value";

$f4->setRenderer($scmp);
$form->addField($f4);


$f4m = new DataInput("field4m", "Select Multi", 1);
$scmp1 = new SelectMultipleField();
$scmp1->setSource($aw2);
$scmp1->list_key = "item_id";
$scmp1->list_label = "item_value";

$f4m->setRenderer($scmp1);
$form->addField($f4m);


$f5 = new DataInput("field5", "Text Area", 1);
$f5->setRenderer(new TextArea());
$form->addField($f5);


$f6 = new DataInput("field10", "Checkbox Single", 0);
$f6->setRenderer(new CheckField());
$f6->setValidator(new EmptyValueValidator());
$f6->setProcessor(new BeanPostProcessor());
$form->addField($f6);

$f6 = new DataInput("field101", "Accept Check", 1);
$f6->setRenderer(new CheckField());
$f6->getRenderer()->setCaption("Accept By Clicking Here");

$f6->setValidator(new EmptyValueValidator());
$f6->setProcessor(new BeanPostProcessor());
$form->addField($f6);


$aw = new ArraySelector(array("CheckboxItem1", "CheckboxItem2", "CheckboxItem3"), "item_id", "item_value");

$f11 = new DataInput("field11", "Checkbox DataSource", 1);

$r11 = new CheckField();
$r11->setSource($aw);
$r11->list_key = "item_value";
$r11->list_label = "item_value";

$f11->setRenderer($r11);
$f11->setValidator(new EmptyValueValidator());
$f11->setProcessor(new BeanPostProcessor());
$form->addField($f11);

$aw = new ArraySelector(array("CheckboxItem1", "CheckboxItem2", "CheckboxItem3"), "item_id", "item_value");

$f11 = new DataInput("field11_req", "Checkbox DataSource<BR><small>Require array value</small>", 1);

$r11 = new CheckField();
$r11->setSource($aw);
$r11->list_key = "item_value";
$r11->list_label = "item_value";

$f11->setRenderer($r11);
$validator = new EmptyValueValidator();
$validator->require_array_value = true;
$f11->setValidator($validator);
$f11->setProcessor(new BeanPostProcessor());
$form->addField($f11);


$aw1 = new ArraySelector(array("RadioItem1", "RadioItem2", "RadioItem3"), "item_id", "item_value");
$f12 = new DataInput("field12", "Radiobox DataSource", 1);
$r12 = new RadioField();
$r12->setSource($aw1);
$r12->list_key = "item_value";
$r12->list_label = "item_value";
$f12->setRenderer($r12);
$f12->setValidator(new EmptyValueValidator());
$f12->setProcessor(new BeanPostProcessor());
$form->addField($f12);


$f7 = new DataInput("field7", "Date", 1);
$f7->setRenderer(new DateField());
$f7->setValidator(new DateValidator());
$f7->setProcessor(new DateInputProcessor());
$form->addField($f7);

$f8 = new DataInput("field8", "Time", 1);
$f8->setRenderer(new TimeField());
$f8->setValidator(new TimeValidator());
$f8->setProcessor(new TimeInputProcessor());
$form->addField($f8);


$f9 = new DataInput("field9", "Phone", 1);
$f9->setRenderer(new PhoneField());
$f9->setValidator(new PhoneValidator());
$f9->setProcessor(new PhoneInputProcessor());
$form->addField($f9);

$f15 = new DataInput("field15", "Hidden", 0);
$f15->setRenderer(new HiddenField());
$form->addField($f15);


$f16 = DataInputFactory::Create(DataInputFactory::CAPTCHA, "captcha_field", "Captcha Code", 1);
$form->addField($f16);

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
