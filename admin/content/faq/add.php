<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/FAQItemInputForm.php");
include_once("class/beans/FAQItemsBean.php");


$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$view = new InputFormView(new FAQItemsBean(), new FAQItemInputForm());


$view->processInput();

$page->startRender($menu);

$view->render();

$page->finishRender();


?>