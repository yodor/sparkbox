<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");


$page = new DemoPage();

$form = new InputForm();

$input = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "photo", "Photo", 1);
$input->getValueTransactor()->max_slots = 4;
$form->addField($input);

$input = DataInputFactory::Create(DataInputFactory::SESSION_FILE, "document", "Document", 1);
$input->getValueTransactor()->max_slots = 4;
$form->addField($input);

$form_render = new FormRenderer();
$form->setRenderer($form_render);
$form->setProcessor(new FormProcessor());
$form->getProcessor()->processForm($form);


$page->startRender();

$form_render->renderForm($form);

$page->finishRender();
?>
