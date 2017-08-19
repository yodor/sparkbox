<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/ProductInputForm.php");
include_once("class/beans/ProductsBean.php");


$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_back = new Action("", Session::get("products.list"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Products");
$page->addAction($action_back);

$view = new InputFormView(new ProductsBean(), new ProductInputForm());

//shortcuts for new ...
Session::set("categories.list", $page->getPageURL());
Session::set("brands.list", $page->getPageURL());
Session::set("classes.list", $page->getPageURL());


$view->getTransactor()->assignInsertValue("insert_date", DBDriver::get()->dateTime());

$view->processInput();

$page->beginPage($menu);

$page->renderPageCaption();

$view->render();

$page->finishPage();

?>
