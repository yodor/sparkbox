<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/AttributesBean.php");
include_once("lib/components/TableView.php");

$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Attribute");
$page->addAction($action_add);

$bean = new AttributesBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("Store Attributes List");
$view->setDefaultOrder(" name ASC ");

$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));
$view->addColumn(new TableColumn("name","Name"));
$view->addColumn(new TableColumn("unit","Unit"));
$view->addColumn(new TableColumn("type","Type"));

$view->addColumn(new TableColumn("actions","Actions"));

$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array(new ActionParameter("editID",$bean->getPrKey()))  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );

$view->getColumn("actions")->setCellRenderer($act);


Session::set("attributes.list", $page->getPageURL());

$page->beginPage($menu);
$page->renderPageCaption();

$view->render();



$page->finishPage();

?>
