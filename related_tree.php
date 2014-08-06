<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

include_once("lib/components/NestedSetTreeView.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductPhotosBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");


$page = new DemoPage();




$bean = new ProductCategoriesBean();
// $photos = new ProductPhotosBean();

$prods = new ProductsBean();


$ir = new TextTreeItemRenderer();

$tv = new NestedSetTreeView();
$tv->setSource($bean);
$tv->setRelatedSource($prods);

$tv->setName("demo_tree");
$tv->open_all = false;

$tv->list_label = "category_name";


$tv->setItemRenderer($ir);


$search_fields = array("product_code", "product_name", "prodID", "product_description", "keywords");
$ksc = new KeywordSearchComponent($search_fields);

$tv->addRelatedFilter("brand","brandID");
// $tv->addRelatedFilter("category","catID");
$tv->addRelatedFilter("search",$ksc);


$have_filter = $tv->processFilters();
//
$tree_query = $tv->getSelectQuery();
$tree_query->where.=" AND products.visible = 1 ";
// echo $tree_query->getSQL();

$product_selector = $tv->getRelationSelect();
$product_selector->from.=" , product_categories ";
$product_selector->where.=" AND products.visible = 1 AND product_categories.catID=products.catID";


// echo $product_selector->getSQL();


$view = new TableView(new SQLResultIterator($product_selector, "prodID"));
$view->setCaption("Products List");

$view->addColumn(new TableColumn($prods->getPrKey(),"ID"));
$view->addColumn(new TableColumn("photo","Photo"));
$view->addColumn(new TableColumn("product_code","Product Code"));
$view->addColumn(new TableColumn("product_name","Product Name"));
$view->addColumn(new TableColumn("brand_name","Brand Name"));
$view->addColumn(new TableColumn("category_name","Category Name"));
// $view->addColumn(new TableColumn("catID","Category"));
// $view->getColumn("catID")->getHeaderCellRenderer()->setSortField("products.catID");
$view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductPhotosBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
$view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);

$page->beginPage();

// echo $product_selector->getSQL();

echo "<div class='column categories'>";

if ($have_filter) {
    echo "<div class='tree_item'>";
    echo "<div class='tree_node_handle icon_handle_leaf'></div>";
    echo "<a  class='ActionRenderer'  href='?'>Show All Categories</a>";
    echo "</div>";
}

$tv->render();



echo "</div>";

echo "<div class='column'>";

$ksc->render();
$view->render();

echo "</div>";

$page->finishPage();


?>
