<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductPhotosBean.php");
include_once("class/beans/ProductInventoryBean.php");

include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");

$menu=array(
    new MenuItem("Categories", "categories/list.php", "list-add.png"),
    new MenuItem("Colors", "colors/list.php?prodID", "list-add.png"),
    new MenuItem("Sizes", "sizes/list.php?prodID", "list-add.png"),
    new MenuItem("Inventory", "inventory/list.php?prodID", "list-add.png"),
    new MenuItem("Add Product", "add.php", "list-add.png"),
    
);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$bean = new ProductsBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

$search_fields = array("product_code", "product_name", "category_name", "color", "size", "prodID", "product_description", "keywords", "brand_name", "gender");
$ksc = new KeywordSearchComponent($search_fields);

$select_products = $bean->getSelectQuery();
// $select_products->fields = " *, sum(stock_amount) as stock_amount, min(price) as price_min, max(price) as price_max, 
// group_concat(color SEPARATOR ';' ) as colors, group_concat(size SEPARATOR ';') as sizes,
// min(weight) as weight_min, max(weight) as weight_max
// ";
$select_products->fields = " p.*, pc.category_name, 
(SELECT group_concat(color) FROM product_colors pclr WHERE pclr.prodID=p.prodID) as colors,
(SELECT group_concat(size_value) FROM product_sizes psz WHERE psz.prodID=p.prodID) as sizes,
(SELECT SUM(stock_amount) FROM product_inventory pinv WHERE pinv.prodID=p.prodID) as stock_amount";

$select_products->from = " products p LEFT JOIN product_categories pc ON pc.catID=p.catID ";
$select_products->group_by = "  prodID ";
$ksc->processSearch($select_products);



$view = new TableView(new SQLResultIterator($select_products, $bean->getPrKey()));
$view->setCaption("Product Inventory List");
$view->setDefaultOrder("  insert_date DESC  ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn("prodID","ID"));

// $view->addColumn(new TableColumn("photo", "Inventory Photo"));

$view->addColumn(new TableColumn("category_name", "Category"));
$view->addColumn(new TableColumn("brand_name","Brand"));

$view->addColumn(new TableColumn("product_code","Product Code"));
$view->addColumn(new TableColumn("product_name","Product Name"));
$view->addColumn(new TableColumn("gender","Gender"));

// $view->addColumn(new TableColumn("buy_price","Buy Price"));
// $view->addColumn(new TableColumn("price_min","Price Min"));
// $view->addColumn(new TableColumn("price_max","Price Max"));
// $view->addColumn(new TableColumn("old_price","Old Price"));

$view->addColumn(new TableColumn("colors", "Colors"));
$view->addColumn(new TableColumn("sizes", "Sizes"));

// $view->addColumn(new TableColumn("weight_min", "Weight Min"));
// $view->addColumn(new TableColumn("weight_max", "Weight Max"));

$view->addColumn(new TableColumn("visible", "Visible"));

$view->addColumn(new TableColumn("promotion", "Promotion"));

$view->addColumn(new TableColumn("view_counter", "View Counter"));
$view->addColumn(new TableColumn("order_counter", "Order Counter"));

$view->addColumn(new TableColumn("stock_amount", "Stock Amount"));


$view->addColumn(new TableColumn("actions","Actions"));
/*
$view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductInventoryPhotoGalleryBean(), TableImageCellRenderer::RENDER_THUMB, -1, 24));
$view->getColumn("photo")->getCellRenderer()->setListLimit(0);
$view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);


$view->getColumn("color_photo")->setCellRenderer(new TableImageCellRenderer(new ProductInventoryBean(), TableImageCellRenderer::RENDER_THUMB, -1, 24));
$view->getColumn("color_photo")->getCellRenderer()->setListLimit(0);
$view->getColumn("color_photo")->getHeaderCellRenderer()->setSortable(false);*/

$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array(new ActionParameter("editID",$bean->getPrKey()))  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );
$act->addAction(  new RowSeparatorAction() );

$act->addAction(
  new Action("Colors", "colors/list.php", array(new ActionParameter("prodID",$bean->getPrKey()))  )
);

$act->addAction(  new PipeSeparatorAction() );

$act->addAction(
  new Action("Sizes", "sizes/list.php", array(new ActionParameter("prodID",$bean->getPrKey()))  )
);

$act->addAction(  new RowSeparatorAction() );

$act->addAction(
  new Action("Inventory", "inventory/list.php", array(new ActionParameter("prodID",$bean->getPrKey()))  )
);

$view->getColumn("actions")->setCellRenderer($act);



$page->beginPage($menu);

$page->renderPageCaption();

$ksc->render();
$view->render();

$page->finishPage();




?>
