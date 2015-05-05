<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");


include_once("class/beans/StoreSizesBean.php");


include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");



$menu=array(
    
);


$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Size");
$page->addAction($action_add);

$bean = new StoreSizesBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("Store Sizing List");
$view->setDefaultOrder(" size_value ASC ");

$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));
$view->addColumn(new TableColumn("size_value","Size"));

$view->addColumn(new TableColumn("actions","Actions"));

$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array( new ActionParameter("editID",$bean->getPrKey()) )  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );
    
$view->getColumn("actions")->setCellRenderer($act);

$page->beginPage($menu);

$page->renderPageCaption();

$view->render();

$page->finishPage();
?>