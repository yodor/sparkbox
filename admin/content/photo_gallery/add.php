<?php
define("DEBUG_OUTPUT", 1);
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/GalleryPhotosBean.php");

include_once("lib/forms/PhotoInputForm.php");



$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$event_photos = new GalleryPhotosBean();


//prefer db_row
$view = new InputFormView($event_photos, new PhotoInputForm());

$form = $view->getForm()->getField("photo")->transact_mode = InputField::TRANSACT_DBROW;

$view->processInput();

$page->beginPage();

$view->render();

$page->finishPage();




?>
