<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/FAQItemsBean.php");
include_once("lib/components/TableView.php");


$menu = array();

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Item");
$page->addAction($action_add);


$bean = new FAQItemsBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView($bean->query());
$view->setCaption("FAQ Items");
$view->setDefaultOrder(" fID DESC ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn($bean->key(), "ID"));
$view->addColumn(new TableColumn("section", "Section"));
$view->addColumn(new TableColumn("question", "Question"));
$view->addColumn(new TableColumn("answer", "Answer"));

$view->addColumn(new TableColumn("actions", "Actions"));


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