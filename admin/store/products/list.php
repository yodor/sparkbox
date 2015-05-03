<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductPhotosBean.php");
include_once("class/beans/ProductColorPhotosBean.php");
include_once("class/beans/ProductInventoryBean.php");

include_once("lib/components/TableView.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");

$menu=array(
	
    new MenuItem("Inventory", "inventory/list.php", "list-add.png"),
//     new MenuItem("Color Gallery", "color_gallery/list.php?prodID", "list-add.png"),
//     new MenuItem("Photo Gallery", "gallery/list.php?prodID", "list-add.png"),
//     new MenuItem("Add Product", "add.php", "list-add.png"),
    
);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Product");
$page->addAction($action_add);

$bean = new ProductsBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

$search_fields = array("product_code", "product_name", "category_name", "class_name", "product_description", "keywords", "brand_name", "gender");
$ksc = new KeywordSearchComponent($search_fields);
$ksc->getForm()->getRenderer()->setAttribute("method", "get");

$select_products = $bean->getSelectQuery();
// $select_products->fields = " *, sum(stock_amount) as stock_amount, min(price) as price_min, max(price) as price_max, 
// group_concat(color SEPARATOR ';' ) as colors, group_concat(size SEPARATOR ';') as sizes,
// min(weight) as weight_min, max(weight) as weight_max
// ";

$select_products->fields = " 
SUM(pi.stock_amount) as stock_amount,
min(pi.price) as price_min, max(pi.price) as price_max,
group_concat(distinct(size_value) SEPARATOR '<BR>') as sizes, 
p.prodID, p.product_name, p.class_name, p.brand_name, p.gender, pc.category_name, p.product_code, p.visible, p.promotion, 
p.price, p.old_price, p.buy_price, cc.pi_ids, replace(cc.colors, '|','<BR>') as colors, cc.color_photos, cc.have_chips, cc.color_ids, cc.product_photos
";

$select_products->from = " product_inventory pi JOIN products p ON p.prodID = pi.prodID JOIN color_chips cc ON cc.prodID = pi.prodID JOIN product_categories pc ON pc.catID=p.catID ";
$select_products->group_by = "  pi.prodID ";
$ksc->processSearch($select_products);


$view = new TableView(new SQLResultIterator($select_products, "prodID"));
$view->setCaption("Product Inventory List");
$view->setDefaultOrder("  insert_date DESC  ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn("prodID","ID"));

$view->addColumn(new TableColumn("photo", "Product Photo"));

$view->addColumn(new TableColumn("color_photos", "Color Gallery"));
$view->addColumn(new TableColumn("colors", "Colors"));

$view->addColumn(new TableColumn("category_name", "Category"));
$view->addColumn(new TableColumn("brand_name","Brand"));
$view->addColumn(new TableColumn("class_name","Class"));
$view->addColumn(new TableColumn("product_name","Product Name"));

$view->addColumn(new TableColumn("product_code","Product Code"));

$view->addColumn(new TableColumn("gender","Gender"));

// $view->addColumn(new TableColumn("buy_price","Buy Price"));
$view->addColumn(new TableColumn("price_min","Price Min"));
$view->addColumn(new TableColumn("price_max","Price Max"));
// $view->addColumn(new TableColumn("old_price","Old Price"));


$view->addColumn(new TableColumn("sizes", "Sizes"));

// $view->addColumn(new TableColumn("weight_min", "Weight Min"));
// $view->addColumn(new TableColumn("weight_max", "Weight Max"));

$view->addColumn(new TableColumn("visible", "Visible"));

$view->addColumn(new TableColumn("promotion", "Promotion"));



$view->addColumn(new TableColumn("stock_amount", "Stock Amount"));


$view->addColumn(new TableColumn("actions","Actions"));

$view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductPhotosBean(), TableImageCellRenderer::RENDER_THUMB, -1, 64));
$view->getColumn("photo")->getCellRenderer()->setListLimit(0);
$view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);

$view->getColumn("color_photos")->setCellRenderer(new TableImageCellRenderer(new ProductColorPhotosBean(), TableImageCellRenderer::RENDER_THUMB, 48, -1));
$view->getColumn("color_photos")->getCellRenderer()->setListLimit(0);
$view->getColumn("color_photos")->getHeaderCellRenderer()->setSortable(false);

$view->getColumn("visible")->setCellRenderer(new BooleanFieldCellRenderer("Yes", "No"));
$view->getColumn("promotion")->setCellRenderer(new BooleanFieldCellRenderer("Yes", "No"));

$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array(new ActionParameter("editID",$bean->getPrKey()))  )
); 
$act->addAction(  new PipeSeparatorAction() );
$act->addAction( $h_delete->createAction() );
$act->addAction(  new RowSeparatorAction() );

$act->addAction(
  new Action("Color Gallery", "color_gallery/list.php", array(new ActionParameter("prodID",$bean->getPrKey()))  )
);
$act->addAction(  new PipeSeparatorAction() );
$act->addAction(
  new Action("Photo Gallery", "gallery/list.php", array(new ActionParameter("prodID",$bean->getPrKey()))  )
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
