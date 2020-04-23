<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");
include_once("lib/forms/InputForm.php");

$page = new DemoPage();


$form = new InputForm();

$input = DataInputFactory::Create(DataInputFactory::MCE_TEXTAREA, "text", "Text", 1);
$form->addField($input);
$handler = $input->getRenderer()->getImageBrowser()->getHandler();

$handler->setSection("mce_image_demo", "text");
$handler->setOwnerID(-1);


$form_render = new FormRenderer(FormRenderer::FIELD_VBOX);
$form->setRenderer($form_render);
$form->setProcessor(new FormProcessor());
$form->getProcessor()->processForm($form);


$page->startRender();

$form_render->renderForm($form);


$page->finishRender();
?>