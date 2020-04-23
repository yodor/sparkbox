<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/BrandInputForm.php");
include_once("class/beans/BrandsBean.php");


$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_back = new Action("", Session::Get("brands.list"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Brands");
$page->addAction($action_back);

$view = new InputFormView(new BrandsBean(), new BrandInputForm());

$view->processInput();

$page->startRender($menu);

$page->renderPageCaption();

$view->render();

$page->finishRender();


?>
