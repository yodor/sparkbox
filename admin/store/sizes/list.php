<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");


include_once("class/beans/StoreSizesBean.php");


include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");
// include_once("class/beans/ProductInventoryPhotosBean.php");





$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$menu=array(
    new MenuItem("Add Size", "add.php", "list-add.png"),
);

$bean = new StoreSizesBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

// $search_fields = array("prodID", "product_code", "product_name", "color", "size");
// $ksc = new KeywordSearchComponent($search_fields);



$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("Store Sizing List");
// $view->setDefaultOrder(" ORDER BY item_date DESC ");
// $view->search_filter = " ORDER BY day_num ASC ";
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

// $ksc->render();
$view->render();

$page->finishPage();




?>
