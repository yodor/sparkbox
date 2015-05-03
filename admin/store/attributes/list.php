<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/AttributesBean.php");
include_once("lib/components/TableView.php");

$menu=array(

    new MenuItem("Add Attribute", "add.php", "list-add.png")
);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);





$bean = new AttributesBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("Global Attributes List");
// $view->setDefaultOrder(" ORDER BY item_date DESC ");
// $view->search_filter = " ORDER BY day_num ASC ";
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

$page->beginPage($menu);
$page->renderPageCaption();

$view->render();



$page->finishPage();

?>