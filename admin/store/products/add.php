<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/ProductInputForm.php");
include_once("class/beans/ProductsBean.php");


$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$view = new InputFormView(new ProductsBean(), new ProductInputForm());

$view->getTransactor()->assignInsertValue("insert_date", DBDriver::get()->dateTime());

$view->processInput();

$page->beginPage($menu);

$view->render();

$page->finishPage();


?>