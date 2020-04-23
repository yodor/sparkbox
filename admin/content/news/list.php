<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/NewsItemsBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");

$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Item");
$page->addAction($action_add);

$bean = new NewsItemsBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("News Items");
$view->setDefaultOrder(" item_date DESC ");

$view->addColumn(new TableColumn($bean->key(), "ID"));
$view->addColumn(new TableColumn("photo", "Photo"));
$view->addColumn(new TableColumn("item_title", "Title"));
$view->addColumn(new TableColumn("item_date", "Date"));

$view->addColumn(new TableColumn("actions", "Actions"));


$view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new NewsItemsBean(), TableImageCellRenderer::RENDER_THUMB, 128, -1));
$view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);

$act = new ActionsTableCellRenderer();
$act->addAction(new Action("Edit", "add.php", array(new ActionParameter("editID", $bean->key()))));
$act->addAction(new PipeSeparatorAction());
$act->addAction($h_delete->createAction());


$view->getColumn("actions")->setCellRenderer($act);

$page->startRender($menu);
$page->renderPageCaption();

$view->render();


$page->finishRender();


?>
