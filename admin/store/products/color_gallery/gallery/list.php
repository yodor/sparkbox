<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductColorPhotosBean.php");
include_once("class/beans/ProductColorsBean.php");


include_once("lib/components/GalleryView.php");



$rc = new ReferenceKeyPageChecker(new ProductColorsBean(), "../list.php".queryString($_GET));

$menu=array();



$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);


$action_back = new Action("", Session::get("inventory.color_scheme"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Color Scheme");
$page->addAction($action_back);

$action_add = new Action("", "add.php?".$rc->ref_key."=".$rc->ref_id, array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Color Scheme Photo");
$page->addAction($action_add);

// $action_add = new Action("", "add.php", array());
// $action_add->setAttribute("action", "add");
// $action_add->setAttribute("title", "Add Photo To Color Gallery");
// $page->addAction($action_add);
$page->setAccessibleTitle("Color Scheme Photos");

$page->setCaption( tr("Color Scheme Photos").": ".$rc->ref_row["color"] );


$bean = new ProductColorPhotosBean();
$bean->setFilter($rc->ref_key."='".$rc->ref_id."'");


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);
$h_repos = new ChangePositionRequestHandler($bean);
RequestController::addRequestHandler($h_repos);


$gv = new GalleryView();
$gv->blob_field = "photo";

$gv->initView($bean, "add.php", $rc->ref_key, $rc->ref_id);

Session::set("color_scheme.photos", $page->getPageURL());

$page->beginPage($menu);

$page->renderPageCaption();

$gv->render();


$page->finishPage();


?>
