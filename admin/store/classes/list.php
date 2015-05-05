<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");


include_once("class/beans/ProductClassesBean.php");


include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");
// include_once("class/beans/ProductInventoryPhotosBean.php");


$menu=array(
    
);


$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);



$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Class");
$page->addAction($action_add);


$bean = new ProductClassesBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("Product Classes List");
$view->setDefaultOrder($bean->getPrKey()." DESC ");
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));
$view->addColumn(new TableColumn("class_name","Class Name"));


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