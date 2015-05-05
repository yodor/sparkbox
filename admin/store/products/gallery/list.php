<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductPhotosBean.php");
include_once("class/beans/ProductsBean.php");


include_once("lib/components/GalleryView.php");

$menu = array();


$rc = new ReferenceKeyPageChecker(new ProductsBean(), "../list.php");

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);
$page->setAccessibleTitle("Photo Gallery");

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Photo To Product Gallery");
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

$page->beginPage($menu);
$page->renderPageCaption();

$gv->render();


$page->finishPage();


?>