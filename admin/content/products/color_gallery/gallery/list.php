<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductColorPhotosBean.php");
include_once("class/beans/ProductColorsBean.php");


include_once("lib/components/GalleryView.php");



$rc = new ReferenceKeyPageChecker(new ProductColorsBean(), "../list.php");


$menu=array(
    new MenuItem("Add Photo","add.php".$rc->qrystr, "list-add.png")
);


$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);
$page->setCaption("Product Color Photo Gallery");

$bean = new ProductColorPhotosBean();
$bean->setFilter($rc->ref_key."='".$rc->ref_id."'");


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);
$h_repos = new ChangePositionRequestHandler($bean);
RequestController::addRequestHandler($h_repos);


$gv = new GalleryView();
$gv->blob_field = "photo";

$gv->initView($bean, "add.php", $rc->ref_key, $rc->ref_id);

$page->beginPage($menu);


$gv->render();


$page->finishPage();


?>