<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/StoreColorInputForm.php");
include_once("class/beans/StoreColorsBean.php");

$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$view = new InputFormView(new StoreColorsBean(), new StoreColorInputForm());


$view->processInput();

$page->beginPage($menu);

$view->render();

$page->finishPage();


?>