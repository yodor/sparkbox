<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/AttributeInputForm.php");
include_once("class/beans/AttributesBean.php");


$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$view = new InputFormView(new AttributesBean(), new AttributeInputForm());

$view->processInput();

$page->beginPage($menu);

$view->render();

$page->finishPage();


?>