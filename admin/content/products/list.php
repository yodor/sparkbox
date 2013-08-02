<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/beans/ProductsBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");
include_once("class/beans/ProductPhotosBean.php");

$menu=array(
    new MenuItem("Categories", "categories/list.php", "list-add.png"),
    new MenuItem("Add Product", "add.php", "list-add.png"),
    
);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$bean = new ProductsBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

$search_fields = array("product_code", "product_name", "prodID", "product_description", "keywords", "brand_name", "gender");
$ksc = new KeywordSearchComponent($search_fields);

$select_products = $bean->getSelectQuery();
$select_products->fields = " products.*, product_categories.category_name ";
$select_products->from = " products JOIN product_categories ON product_categories.catID = products.catID ";

$ksc->processSearch($select_products);



$view = new TableView(new SQLResultIterator($select_products, $bean->getPrKey()));
$view->setCaption("Products List");
// $view->setDefaultOrder(" ORDER BY item_date DESC ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));
$view->addColumn(new TableColumn("photo","Photo"));

$view->addColumn(new TableColumn("category_name", "Category"));
$view->addColumn(new TableColumn("brand_name","Brand"));

$view->addColumn(new TableColumn("product_code","Product Code"));
$view->addColumn(new TableColumn("product_name","Product Name"));
$view->addColumn(new TableColumn("gender","Gender"));

$view->addColumn(new TableColumn("buy_price","Buy Price"));
$view->addColumn(new TableColumn("sell_price","Sell Price"));
$view->addColumn(new TableColumn("old_price","Old Price"));

$view->addColumn(new TableColumn("visible", "Visible"));

$view->addColumn(new TableColumn("promotion", "Promotion"));

$view->addColumn(new TableColumn("view_counter", "View Counter"));
$view->addColumn(new TableColumn("order_counter", "Order Counter"));

$view->addColumn(new TableColumn("actions","Actions"));


$view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductPhotosBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
$view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);

$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array(new ActionParameter("editID",$bean->getPrKey()))  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );

$view->getColumn("actions")->setCellRenderer($act);



$page->beginPage($menu);

$page->renderPageCaption();

$ksc->render();
$view->render();

$page->finishPage();




?>
