<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/ProductInventoryInputForm.php");
include_once("class/beans/ProductInventoryBean.php");
include_once("class/beans/ProductsBean.php");

$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_back = new Action("", Session::get("products.inventory"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back To Inventory List");
$page->addAction($action_back);

Session::set("sizing.list", $page->getPageURL());
Session::set("product.color_scheme", $page->getPageURL());

$ensure_product = new ReferenceKeyPageChecker(new ProductsBean(), "../list.php");
$prodID = (int)$ensure_product->ref_id;


$form = new ProductInventoryInputForm();
$form->setProductID($prodID);

$view = new InputFormView(new ProductInventoryBean(), $form);
$view->reload_url = Session::get("inventory.list");

// $view->getTransactor()->assignInsertValue("insert_date", DBDriver::get()->dateTime());
$view->getTransactor()->appendValue("prodID", $prodID);


$page->setCaption("Inventory: ".$ensure_product->ref_row["product_name"]);


$view->processInput();



$page->beginPage($menu);

$page->renderPageCaption();

$view->render();

$page->finishPage();


?>
