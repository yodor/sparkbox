<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductColorsBean.php");

include_once("class/beans/ProductInventoryBean.php");
include_once("class/beans/ProductColorPhotosBean.php");

include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");





$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_back = new Action("", Session::get("products.list"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Products");
$page->addAction($action_back);


$rc = new ReferenceKeyPageChecker(new ProductsBean(), "../list.php");




$menu=array(
//    new MenuItem("Color Gallery", "list.php".$rc->qrystr, "list-add.png"),
//    new MenuItem("Add Gallery", "add.php".$rc->qrystr, "list-add.png")
);

$action_add = new Action("", "add.php?".$rc->ref_key."=".$rc->ref_id, array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Color Scheme");
$page->addAction($action_add);

$page->setAccessibleTitle("Color Scheme");

$bean = new ProductColorsBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

// $search_fields = array("prodID", "product_code", "product_name", "color", "size");
// $ksc = new KeywordSearchComponent($search_fields);

$select_colors = $bean->getSelectQuery();
$select_colors->fields = " pclr.*, p.product_name, p.product_code ";
$select_colors->from = " product_colors pclr LEFT JOIN products p ON p.prodID = pclr.prodID ";
$select_colors->where = " pclr.prodID = ".$rc->ref_id;



$page->setCaption( tr("Color Scheme").": ".$rc->ref_row["product_name"] );





// $ksc->processSearch($select_products);



$view = new TableView(new SQLResultIterator($select_colors, $bean->getPrKey()));
$view->setCaption("Color Schemes List");
// $view->setDefaultOrder(" ORDER BY item_date DESC ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));

// if ($prodID<1) {
//   $view->addColumn(new TableColumn("product_name","Product Name"));
// }

$view->addColumn(new TableColumn("photo","Scheme Photos"));

$view->addColumn(new TableColumn("color", "Color Name"));

$view->addColumn(new TableColumn("color_photo","Color Chip"));


$view->addColumn(new TableColumn("actions","Actions"));


$view->getColumn("color_photo")->setCellRenderer(new TableImageCellRenderer(new ProductColorsBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
$view->getColumn("color_photo")->getCellRenderer()->setBlobField("color_photo");
$view->getColumn("color_photo")->getHeaderCellRenderer()->setSortable(false);



$view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductColorPhotosBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
$view->getColumn("photo")->getCellRenderer()->setListLimit(0);
$view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);


$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array(  new ActionParameter("editID",$bean->getPrKey()) )  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );

$act->addAction(  new RowSeparatorAction() );

$act->addAction(
      new Action("Photos", "gallery/list.php", 
		array(
		  new ActionParameter($bean->getPrKey(), $bean->getPrKey())
		)
      )
);
    
$view->getColumn("actions")->setCellRenderer($act);

Session::set("product.color_scheme", $page->getPageURL());


$page->beginPage($menu);

$page->renderPageCaption();

// $ksc->render();
$view->render();

$page->finishPage();




?>
