<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/AttributeInputForm.php");
include_once("class/beans/AttributesBean.php");


$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_back = new Action("", Session::Get("attributes.list"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back");

$page->addAction($action_back);

$view = new InputFormView(new AttributesBean(), new AttributeInputForm());

$view->processInput();

$page->startRender($menu);

$page->renderPageCaption();

$view->render();

$page->finishRender();


?>
