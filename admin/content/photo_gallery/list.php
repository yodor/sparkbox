<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/GalleryPhotosBean.php");

include_once("lib/components/GalleryView.php");



$menu=array(
	new MenuItem("Add Photo","add.php", "list-add.png")
);


$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);


$bean = new GalleryPhotosBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

$h_repos = new ChangePositionRequestHandler($bean);
RequestController::addRequestHandler($h_repos);


$gv = new GalleryView();
$gv->blob_field = "photo";
$gv->setCaption("Sample Photo Gallery Items");
$gv->initView($bean, "add.php");


$page->beginPage($menu);
$page->renderPageCaption();

$gv->render();

$page->finishPage();


?>