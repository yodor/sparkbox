<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
// include_once("class/beans/ProductsBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");

include_once("class/beans/ProductInventoryBean.php");
include_once("class/beans/ProductPhotosBean.php");
include_once("class/beans/ProductColorPhotosBean.php");
include_once("class/beans/ProductsBean.php");


$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$menu = array();

$action_back = new Action("", Session::Get("products.list"), array());
$action_back->setAttribute("action", "back");
$action_back->setAttribute("title", "Back to Products");
$page->addAction($action_back);

$prodID = -1;

try {

    $rc = new ReferenceKeyPageChecker(new ProductsBean(), false);
    //   $menu=array(
    // 	  new MenuItem("Add Inventory", "add.php".$rc->qrystr, "list-add.png"),
    //   );
    $prodID = (int)$rc->ref_id;

    $action_add = new Action("", "add.php?prodID=$prodID", array());
    $action_add->setAttribute("action", "add");
    $action_add->setAttribute("title", "Add Inventory");
    $page->addAction($action_add);


}
catch (Exception $e) {

}

$bean = new ProductInventoryBean();


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

// $search_fields = array("prodID", "product_code", "product_name", "color", "size");
// $ksc = new KeywordSearchComponent($search_fields);

$select_inventory = $bean->selectQuery();
$select_inventory->fields = " pi.*, pi.prodID as product_photo, pclr.pclrID,  sc.color_code, pi.size_value, p.product_name, p.product_code  ";
$select_inventory->from = " product_inventory pi LEFT JOIN product_colors pclr ON pclr.pclrID = pi.pclrID LEFT JOIN store_colors sc ON sc.color=pclr.color LEFT JOIN products p ON p.prodID = pi.prodID JOIN color_chips cc ON cc.prodID = p.prodID LEFT JOIN product_photos pp ON pp.prodID = pi.prodID ";
$select_inventory->group_by = " pi.piID ";
if ($prodID > 0) {
    $select_inventory->where = " pi.prodID = '$prodID' ";
    $page->caption = tr("Inventory") . ": " . $rc->ref_row["product_name"];
}
else {
    $page->caption = tr("All Products Inventory");
}


// $ksc->processSearch($select_products);


$view = new TableView(new SQLResultIterator($select_inventory, "piID"));
$view->setCaption("Inventory List");
$view->setDefaultOrder(" piID DESC ");

$view->addColumn(new TableColumn("piID", "ID"));


$view->addColumn(new TableColumn("prodID", "ProdID"));

$view->addColumn(new TableColumn("product_photo", "Product Photo"));

$view->addColumn(new TableColumn("product_name", "Product Name"));
$view->addColumn(new TableColumn("product_code", "Product Code"));


$view->addColumn(new TableColumn("pclrID", "Color Scheme"));

$view->addColumn(new TableColumn("color", "Color Name"));
$view->addColumn(new TableColumn("color_code", "Color Code"));
$view->addColumn(new TableColumn("size_value", "Size"));
$view->addColumn(new TableColumn("stock_amount", "Stock Amount"));


$view->addColumn(new TableColumn("price", "Price"));
$view->addColumn(new TableColumn("buy_price", "Buy Price"));
$view->addColumn(new TableColumn("old_price", "Old Price"));
$view->addColumn(new TableColumn("weight", "Weight"));


$view->addColumn(new TableColumn("actions", "Actions"));

$view->getColumn("product_photo")->setCellRenderer(new TableImageCellRenderer(new ProductPhotosBean(), -1, 64));
$view->getColumn("product_photo")->getCellRenderer()->setSourceIteratorKey("prodID");
$view->getColumn("product_photo")->getCellRenderer()->setListLimit(1);
$view->getColumn("product_photo")->getHeaderCellRenderer()->setSortable(false);

$view->getColumn("pclrID")->setCellRenderer(new TableImageCellRenderer(new ProductColorPhotosBean(), -1, 64));
$view->getColumn("pclrID")->getCellRenderer()->setSourceIteratorKey("pclrID");
$view->getColumn("pclrID")->getHeaderCellRenderer()->setSortable(false);

// $view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductInventoryPhotosBean(), -1, 64));
// $view->getColumn("photo")->getCellRenderer()->setListLimit(1);
// $view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);

$act = new ActionsTableCellRenderer();
$act->addAction(new Action("Edit", "add.php", array(new ActionParameter("prodID", "prodID"), new ActionParameter("editID", $bean->key()))));
$act->addAction(new PipeSeparatorAction());
$act->addAction($h_delete->createAction());

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

Session::Set("products.inventory", $page->getPageURL());

$page->startRender($menu);

$page->renderPageCaption();

// $ksc->render();
$view->render();

$page->finishRender();


?>
