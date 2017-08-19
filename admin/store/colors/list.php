<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductsBean.php");
include_once("class/beans/StoreColorsBean.php");

include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/renderers/cells/ColorCodeCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");



$menu=array(

);


$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Color");
$page->addAction($action_add);



$bean = new StoreColorsBean();



$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);



$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("Available Colors");
$view->setDefaultOrder(" color ASC ");

$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));


$view->addColumn(new TableColumn("color", "Color"));
$view->addColumn(new TableColumn("color_code", "Color Code"));

$view->addColumn(new TableColumn("actions","Actions"));

$view->getColumn("color_code")->setCellRenderer(new ColorCodeCellRenderer());


$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array(  new ActionParameter("editID",$bean->getPrKey()) )  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );

$act->addAction(  new RowSeparatorAction() );


    
$view->getColumn("actions")->setCellRenderer($act);

Session::set("color_codes.list", $page->getPageURL());

$page->beginPage($menu);

$page->renderPageCaption();

// $ksc->render();
$view->render();

$page->finishPage();




?>
