<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/StoreColorInputForm.php");
include_once("class/beans/StoreColorsBean.php");

$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_back = new Action("", Session::Get("color_codes.list"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Color Codes");
$page->addAction($action_back);

$view = new InputFormView(new StoreColorsBean(), new StoreColorInputForm());


$view->processInput();

$page->startRender($menu);

$page->renderPageCaption();

$view->render();

$page->finishRender();


?>
