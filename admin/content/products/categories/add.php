<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/ProductCategoryInputForm.php");
include_once("class/beans/ProductCategoriesBean.php");


$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$view = new InputFormView(new ProductCategoriesBean(), new ProductCategoryInputForm());

$view->processInput();

$page->beginPage($menu);

$view->render();

$page->finishPage();


?>