<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/ProductColorInputForm.php");
include_once("class/beans/ProductColorsBean.php");
include_once("class/beans/ProductsBean.php");

$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_back = new Action("", Session::Get("product.color_scheme"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Colors List");
$page->addAction($action_back);

$ensure_product = new ReferenceKeyPageChecker(new ProductsBean(), "../list.php");


$view = new InputFormView(new ProductColorsBean(), new ProductColorInputForm($ensure_product->ref_id));

Session::Set("color_codes.list", $page->getPageURL());

// $view->getTransactor()->assignInsertValue("insert_date", DBDriver::get()->dateTime());
$view->getTransactor()->appendValue("prodID", $ensure_product->ref_id);


$page->setCaption("Color Scheme: " . $ensure_product->ref_row["product_name"]);

$view->processInput();

$page->startRender($menu);

$page->renderPageCaption();

$view->render();

$page->finishRender();


?>
