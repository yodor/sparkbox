<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");
include_once("lib/forms/InputForm.php");
include_once("lib/input/InputFactory.php");
// include_once("lib/handlers/JSONResponse.php");
// include_once("lib/handlers/UploadControlAjaxHandler.php");



$page = new DemoPage();



// $ucah = new UploadControlAjaxHandler($page);
// 
// RequestController::addAjaxHandler($ucah);


$form = new InputForm();

$input = InputFactory::CreateField(InputFactory::SESSION_IMAGE, "photo","Photo", 1);
$input->getValueTransactor()->max_slots = 4;
// $input->getRenderer()->assignUploadHandler($ucah);
$form->addField($input);

$input = InputFactory::CreateField(InputFactory::SESSION_FILE, "document", "Document", 1);
$input->getValueTransactor()->max_slots = 4;
// $input->getRenderer()->assignUploadHandler($ucah);
$form->addField($input);

$form_render = new FormRenderer();
$form->setRenderer($form_render);
$form->setProcessor(new FormProcessor());
$form->getProcessor()->processForm($form);


$page->beginPage();

$form_render->renderForm($form);

$page->finishPage();
?>