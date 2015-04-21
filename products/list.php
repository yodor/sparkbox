<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

include_once("lib/components/NestedSetTreeView.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductPhotosBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/ListView.php");

include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");
include_once("lib/components/renderers/items/ItemRendererImpl.php");

include_once("class/beans/SellableProductsView.php");

include_once("class/components/renderers/items/ProductListItem.php");

$page = new DemoPage();




$bean = new ProductCategoriesBean();

//TODO: keep only one view (2) as we can group with product_select parameter?
$prods = new SellableProductsView();


$ir = new TextTreeItemRenderer();

$tv = new NestedSetTreeView();
$tv->setSource($bean);

$tv->setRelatedSource($prods);//, " count(distinct(sellable_products_view.prodID)) ");

$tv->setName("demo_tree");
$tv->open_all = false;

$tv->list_label = "category_name";


$tv->setItemRenderer($ir);


$search_fields = array("product_code", "product_name", "prodID", "product_description", "keywords");
$ksc = new KeywordSearchComponent($search_fields);
$ksc->getForm()->getRenderer()->setAttribute("method","get");

$tv->addRelatedFilter("brand","brandID");
// $tv->addRelatedFilter("gender","gender");
$tv->addRelatedFilter("search",$ksc);

$have_filter = $tv->processFilters();

//
$tree_query = $tv->getSelectQuery();
// echo $tree_query->getSQL();
echo "<HR>";


$product_selector = $tv->getRelationSelect();
// $product_selector->group_by =" sellable_products_view.prodID ";
// echo $product_selector->getSQL();






$view = new ListView(new SQLResultIterator($product_selector, "piID"));
$view->setCaption("Products List");

// $view->addColumn(new TableColumn($prods->getPrKey(),"ID"));
// $view->addColumn(new TableColumn("photo","Photo"));
// $view->addColumn(new TableColumn("product_code","Product Code"));
// $view->addColumn(new TableColumn("product_name","Product Name"));
// $view->addColumn(new TableColumn("brand_name","Brand Name"));
// $view->addColumn(new TableColumn("category_name","Category Name"));
// // $view->addColumn(new TableColumn("catID","Category"));
// // $view->getColumn("catID")->getHeaderCellRenderer()->setSortField("products.catID");
// $view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductPhotosBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
// $view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);

$view->setItemRenderer(new ProductListItem());

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
