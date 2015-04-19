<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
// include_once("class/beans/ProductsBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");

include_once("class/beans/ProductInventoryBean.php");
include_once("class/beans/ProductsBean.php");



$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$rc = new ReferenceKeyPageChecker(new ProductsBean(), "../list.php");


$menu=array(
    new MenuItem("Add Inventory", "add.php".$rc->qrystr, "list-add.png"),

);


$bean = new ProductInventoryBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

// $search_fields = array("prodID", "product_code", "product_name", "color", "size");
// $ksc = new KeywordSearchComponent($search_fields);

$select_inventory = $bean->getSelectQuery();
$select_inventory->fields = " pi.*, pclr.color, psz.size_value, p.product_name, p.product_code  ";
$select_inventory->from = " product_inventory pi LEFT JOIN product_colors pclr ON pclr.pclrID = pi.pclrID LEFT JOIN product_sizes psz ON psz.pszID=pi.pszID LEFT JOIN products p ON p.prodID = pi.prodID ";
$select_inventory->where = " pi.prodID = ".$rc->ref_id."   ";

$list_caption = $rc->ref_row["product_name"];


// $ksc->processSearch($select_products);



$view = new TableView(new SQLResultIterator($select_inventory, $bean->getPrKey()));
$view->setCaption($list_caption);
// $view->setDefaultOrder(" ORDER BY item_date DESC ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));


// $view->addColumn(new TableColumn("photo","Photo"));

// $view->addColumn(new TableColumn("color_photo","Color Photo"));
$view->addColumn(new TableColumn("product_name", "Product Name"));
$view->addColumn(new TableColumn("product_code", "Product Code"));

$view->addColumn(new TableColumn("color", "Color"));
$view->addColumn(new TableColumn("size_value","Size"));
$view->addColumn(new TableColumn("stock_amount","Stock Amount"));


// 
$view->addColumn(new TableColumn("price","Price"));
$view->addColumn(new TableColumn("buy_price","Buy Price"));
$view->addColumn(new TableColumn("old_price","Old Price"));
$view->addColumn(new TableColumn("weight","Weight"));


$view->addColumn(new TableColumn("actions","Actions"));


// $view->getColumn("color_photo")->setCellRenderer(new TableImageCellRenderer(new ProductInventoryBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
// $view->getColumn("color_photo")->getHeaderCellRenderer()->setSortable(false);
// 
// $view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductInventoryPhotosBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
// $view->getColumn("photo")->getCellRenderer()->setListLimit(1);
// $view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);

$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array( new ActionParameter("prodID","prodID"), new ActionParameter("editID",$bean->getPrKey()) )  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );

// $act->addAction(  new RowSeparatorAction() );

// $act->addAction(
//       new Action("Photos", "gallery/list.php", 
// 		array(
// 		  new ActionParameter($bean->getPrKey(), $bean->getPrKey()),
// 		  new ActionParameter("prodID", "prodID")
// 		)
//       )
// );
//     
$view->getColumn("actions")->setCellRenderer($act);



$page->beginPage($menu);

$page->renderPageCaption();

// $ksc->render();
$view->render();

$page->finishPage();




?>
