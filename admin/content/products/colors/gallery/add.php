<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/ProductColorsBean.php");
include_once("class/beans/ProductColorPhotosBean.php");

include_once("lib/forms/PhotoInputForm.php");



$ref_key="";
$ref_val="";
$qrystr=refkeyPageCheck(new ProductColorsBean(), "../list.php", $ref_key, $ref_id);

$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$photos = new ProductColorPhotosBean();
$photos->setFilter("$ref_key='$ref_id'");

$view = new InputFormView($photos, new PhotoInputForm());

//current version of dynamic page photos table is set to DBROWS
$view->getForm()->getField("photo")->transact_mode = InputField::TRANSACT_OBJECT;

$view->getTransactor()->appendValue($ref_key, $ref_id);

$view->processInput();

$page->beginPage($menu);

$view->render();

$page->finishPage();


?>