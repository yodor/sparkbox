<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/BrandsBean.php");
include_once("lib/components/TableView.php");

$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Brand");
$page->addAction($action_add);


$bean = new BrandsBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView($bean->query());
$view->setCaption("Brands List");
$view->setDefaultOrder(" brand_name ASC ");

$view->addColumn(new TableColumn($bean->key(), "ID"));
$view->addColumn(new TableColumn("brand_name", "Name"));
$view->addColumn(new TableColumn("summary", "Summary"));
$view->addColumn(new TableColumn("url", "URL"));


$view->addColumn(new TableColumn("actions", "Actions"));


$act = new ActionsTableCellRenderer();
$act->addAction(new Action("Edit", "add.php", array(new ActionParameter("editID", $bean->key()))));
$act->addAction(new PipeSeparatorAction());
$act->addAction($h_delete->createAction());


$view->getColumn("actions")->setCellRenderer($act);

Session::Set("brands.list", $page->getPageURL());

$page->startRender($menu);
$page->renderPageCaption();

$view->render();


$page->finishRender();

?>
