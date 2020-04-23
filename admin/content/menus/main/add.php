<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/forms/MenuItemInputForm.php");
include_once("lib/beans/MenuItemsBean.php");


$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$bean = new MenuItemsBean();

$view = new InputFormView($bean, new MenuItemInputForm($bean));

$view->processInput();

$page->startRender($menu);


$view->render();


$qry = $_GET;
if (isset($qry["page_id"])) unset($qry["page_id"]);
if (isset($qry["page_class"])) unset($qry["page_class"]);

$_SESSION["chooser_return"] = $_SERVER['PHP_SELF'] . queryString($qry);

$page->finishRender();
?>