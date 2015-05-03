<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("lib/components/NestedSetTreeView.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");


$menu=array(

    new MenuItem("Add Category", "add.php", "list-add.png")
);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);



$bean = new ProductCategoriesBean();

$h_repos = new ChangePositionRequestHandler($bean);
RequestController::addRequestHandler($h_repos);

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$ir = new TextTreeItemRenderer();
$ir->addAction(new Action("Up", "?cmd=reposition&type=left", array(new ActionParameter("item_id", $bean->getPrKey()))));
$ir->addAction(new Action("Down", "?cmd=reposition&type=right", array(new ActionParameter("item_id", $bean->getPrKey()))));

$ir->addAction(new Action("Edit", "add.php", array(new ActionParameter("editID", $bean->getPrKey()))));
$ir->addAction($h_delete->createAction());


$tv = new NestedSetTreeView();
$tv->setSource($bean);
$tv->setName("ProductCategores");

// $tv->select_qry->group_by = "  node.menuID ";
$tv->setItemRenderer($ir);



$page->beginPage($menu);
$page->renderPageCaption();

$tv->render();

$page->finishPage();




?>
