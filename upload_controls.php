<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

$page = new DemoPage();

$form = new InputForm();


include_once("lib/input/renderers/FileField.php");
include_once("lib/input/validators/FileUploadValidator.php");

include_once("lib/input/renderers/ImageField.php");
include_once("lib/input/validators/ImageUploadValidator.php");

include_once("lib/input/processors/UploadDataInputProcessor.php");

$f16 = new DataInput("field16", "File Field", 0);
$f16->setRenderer(new FileField());
$f16->setValidator(new FileUploadValidator());
$f16->setProcessor(new UploadDataInputProcessor());
$form->addField($f16);

$f17 = new DataInput("field17", "Image Field", 0);

$image_field = new ImageField();
$image_field->setPhotoSize(-1, 128);

$f17->setRenderer($image_field);
$f17->setValidator(new ImageUploadValidator());
$f17->setProcessor(new UploadDataInputProcessor());
$form->addField($f17);


$form_render = new FormRenderer();
$form_render->setAttribute("name", "myform");
$form_render->setAttribute("id", "myform");

$form->setRenderer($form_render);
$form->setProcessor(new FormProcessor());


$form->getProcessor()->processForm($form);

$page->startRender();


$form_render->renderForm($form);


$page->finishRender();


?>