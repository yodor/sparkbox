<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("lib/beans/MenuItemsBean.php");

include_once("lib/components/NestedSetTreeView2.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");

$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Menu Item");
$page->addAction($action_add);

$bean = new MenuItemsBean();

$h_repos = new ChangePositionRequestHandler($bean);
RequestController::addRequestHandler($h_repos);

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$ir = new TextTreeItemRenderer();
$ir->addAction(new Action("Up", "?cmd=reposition&type=left", array(new ActionParameter("item_id", $bean->key()))));
$ir->addAction(new Action("Down", "?cmd=reposition&type=right", array(new ActionParameter("item_id", $bean->key()))));

$ir->addAction(new Action("Edit", "add.php", array(new ActionParameter("editID", $bean->key()))));
$ir->addAction($h_delete->createAction());


$tv = new NestedSetTreeView();
$tv->setName("MenuItemsBean");

$tv->setSource($bean);
$tv->list_label = "menu_title";
$tv->setItemRenderer($ir);


$page->startRender($menu);

$page->renderPageCaption();

$tv->render();


$page->finishRender();

?>
