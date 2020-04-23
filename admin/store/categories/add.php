<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/ProductCategoryInputForm.php");
include_once("class/beans/ProductCategoriesBean.php");


$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_back = new Action("", Session::Get("categories.list"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Categories");
$page->addAction($action_back);

$view = new InputFormView(new ProductCategoriesBean(), new ProductCategoryInputForm());

$view->processInput();

$page->startRender($menu);

$page->renderPageCaption();

$view->render();

$page->finishRender();


?>
