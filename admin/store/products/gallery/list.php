<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductPhotosBean.php");
include_once("class/beans/ProductsBean.php");


include_once("lib/components/GalleryView.php");

$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);
$page->setAccessibleTitle("Photo Gallery");

$action_back = new Action("", Session::get("products.list"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Products");
$page->addAction($action_back);

$rc = new ReferenceKeyPageChecker(new ProductsBean(), "../list.php");



$action_add = new Action("", "add.php?".$rc->ref_key."=".$rc->ref_id, array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Photo");
$page->addAction($action_add);


$page->setCaption( tr("Product Gallery").": ".$rc->ref_row["product_name"] );

$bean = new ProductPhotosBean();
$bean->setFilter($rc->ref_key."='".$rc->ref_id."'");


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);
$h_repos = new ChangePositionRequestHandler($bean);
RequestController::addRequestHandler($h_repos);


$gv = new GalleryView();
$gv->blob_field = "photo";

$gv->initView($bean, "add.php", $rc->ref_key, $rc->ref_id);

Session::set("products.gallery", $page->getPageURL());

$page->beginPage($menu);
$page->renderPageCaption();

$gv->render();


$page->finishPage();


?>
