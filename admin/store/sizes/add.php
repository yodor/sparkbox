<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/StoreSizeInputForm.php");
include_once("class/beans/StoreSizesBean.php");
include_once("class/beans/ProductsBean.php");

$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);



$view = new InputFormView(new StoreSizesBean(), new StoreSizeInputForm());

// $view->getTransactor()->assignInsertValue("insert_date", DBDriver::get()->dateTime());
// $view->getTransactor()->appendValue("prodID", $ensure_product->ref_id);

// $view->setCaption("Product Name: ".$ensure_product->ref_row["product_name"]);

$view->processInput();

$page->beginPage($menu);

$view->render();

$page->finishPage();


?>