<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");


include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductSizesBean.php");


include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");
// include_once("class/beans/ProductInventoryPhotosBean.php");





$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$rc = new ReferenceKeyPageChecker(new ProductsBean(), "../list.php");


$menu=array(
    new MenuItem("Add Size", "add.php".$rc->qrystr, "list-add.png"),
);

$bean = new ProductSizesBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

// $search_fields = array("prodID", "product_code", "product_name", "color", "size");
// $ksc = new KeywordSearchComponent($search_fields);

$select_sizes = $bean->getSelectQuery();
$select_sizes->fields = " psz.*, p.product_name, p.product_code ";
$select_sizes->from = " product_sizes psz LEFT JOIN products p ON p.prodID = psz.prodID ";
$select_sizes->where = " psz.prodID = ".$rc->ref_id;


$list_caption = $rc->ref_row["product_name"];


// $ksc->processSearch($select_products);



$view = new TableView(new SQLResultIterator($select_sizes, $bean->getPrKey()));
$view->setCaption($list_caption);
// $view->setDefaultOrder(" ORDER BY item_date DESC ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));



$view->addColumn(new TableColumn("size_value","Size"));


$view->addColumn(new TableColumn("actions","Actions"));


$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array( new ActionParameter("prodID","prodID"), new ActionParameter("editID",$bean->getPrKey()) )  )
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
