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


$ensure_product = new ReferenceKeyPageChecker(new ProductsBean(), "../list.php");
$prodID = (int)$ensure_product->ref_id;

$form = new ProductInventoryInputForm();
$form->setProductID($prodID);

$view = new InputFormView(new ProductInventoryBean(), $form);

// $view->getTransactor()->assignInsertValue("insert_date", DBDriver::get()->dateTime());
$view->getTransactor()->appendValue("prodID", $prodID);


$view->setCaption("Inventory: ".$ensure_product->ref_row["product_name"]);

$view->processInput();

$page->beginPage($menu);

$view->render();

$page->finishPage();


?>