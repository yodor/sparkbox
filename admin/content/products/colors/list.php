<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductsBean.php");
include_once("class/beans/StoreColorsBean.php");

// include_once("class/beans/ProductSizesBean.php");
// include_once("class/beans/ProductInventoryBean.php");
// include_once("class/beans/ProductColorPhotosBean.php");

include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/renderers/cells/ColorCodeCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");





$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);



$menu=array(
   new MenuItem("Add Color", "add.php", "list-add.png")
);


$bean = new StoreColorsBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

// $search_fields = array("prodID", "product_code", "product_name", "color", "size");
// $ksc = new KeywordSearchComponent($search_fields);

// $select_colors = $bean->getSelectQuery();
// $select_colors->fields = " sclr.*, p.product_name, p.product_code ";
// $select_colors->from = " product_colors pclr LEFT JOIN products p ON p.prodID = pclr.prodID ";
// $select_colors->where = " pclr.prodID = ".$rc->ref_id;


// $list_caption = $rc->ref_row["product_name"];




// $ksc->processSearch($select_products);



$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("Available Colors");
// $view->setDefaultOrder(" ORDER BY item_date DESC ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));


$view->addColumn(new TableColumn("color", "Color"));
$view->addColumn(new TableColumn("color_code", "Color Code"));

$view->addColumn(new TableColumn("actions","Actions"));

$view->getColumn("color_code")->setCellRenderer(new ColorCodeCellRenderer());


// $view->getColumn("color_photo")->setCellRenderer(new TableImageCellRenderer(new ProductColorsBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
// $view->getColumn("color_photo")->getHeaderCellRenderer()->setSortable(false);
// 
// $view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductColorPhotosBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
// $view->getColumn("photo")->getCellRenderer()->setListLimit(1);
// $view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);


$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array(  new ActionParameter("editID",$bean->getPrKey()) )  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );

$act->addAction(  new RowSeparatorAction() );

// $act->addAction(
//       new Action("Gallery", "gallery/list.php", 
// 		array(
// 		  new ActionParameter($bean->getPrKey(), $bean->getPrKey())
// 		)
//       )
// );
    
$view->getColumn("actions")->setCellRenderer($act);



$page->beginPage($menu);

$page->renderPageCaption();

// $ksc->render();
$view->render();

$page->finishPage();




?>
