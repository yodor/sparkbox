<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/GalleryPhotosBean.php");

include_once("lib/forms/PhotoInputForm.php");


$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$event_photos = new GalleryPhotosBean();


//prefer db_row
$view = new InputFormView($event_photos, new PhotoInputForm());

$form = $view->getForm()->getInput("photo")->transact_mode = DataInput::TRANSACT_DBROW;

$view->processInput();

$page->startRender();

$view->render();

$page->finishRender();


?>
