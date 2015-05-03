<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

include_once("lib/components/NestedSetTreeView1.php");
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
include_once("class/beans/BrandsBean.php");

$page = new DemoPage();




$bean = new ProductCategoriesBean();

//TODO: keep only one view (2) as we can group with product_select parameter?
$prods = new SellableProductsView();


$ir = new TextTreeItemRenderer();

$tv = new NestedSetTreeView();
$tv->setSource($bean);

$tv->setRelatedSource($prods);// , " count(distinct(sellable_products.prodID)) ");

$tv->setName("demo_tree");
$tv->open_all = false;

$tv->list_label = "category_name";


$tv->setItemRenderer($ir);


$search_fields = array("product_code", "product_name", "prodID", "product_description", "keywords");
$ksc = new KeywordSearchComponent($search_fields);
$ksc->getForm()->getRenderer()->setAttribute("method","get");

$tv->addRelatedFilter("search",$ksc);

$tv->addCombiningFilter("brand_name","brand_name");
$tv->addCombiningFilter("color","color");

class SizingFilter implements IQueryFilter
{
  public function getQueryFilter($view, $value = NULL)
  {
	$sel = NULL;
	$related_table =  $view->getRelatedSource()->getTableName();
	$related_prkey = $view->getRelatedSource()->getPrKey();
	
	if ($value) {
	  $sel = new SelectQuery();
	  $sel->fields = "";
	  $sel->from = "";
	  if (strcmp($value, "N/A")==0 || strcmp($value, "NULL")==0) {
		$sel->where = " $related_table.size_values IS NULL ";
	  }
	  else {
		$sel->where = " ($related_table.size_values LIKE '%$value|%' OR $related_table.size_values LIKE '%|$value%' OR $related_table.size_values='$value') ";
	  }
	}
	return $sel;
  }
}
$tv->addCombiningFilter("size_values", new SizingFilter());

class PricingFilter implements IQueryFilter
{
  public function getQueryFilter($view, $value = NULL)
  {
	$sel = NULL;
	$related_table =  $view->getRelatedSource()->getTableName();
	$related_prkey = $view->getRelatedSource()->getPrKey();
	
	if ($value) {
	  $sel = new SelectQuery();
	  $sel->fields = "";
	  $sel->from = "";
	  
	  $price_range = explode("|", $value);
	  if (count($price_range)==2) {
		  $price_min = (float)$price_range[0];
		  $price_max = (float)$price_range[1];
		  
		  $sel->where = " ($related_table.price_min >= $price_min AND $related_table.price_max <= $price_max) ";
	  }
	  
	}
	return $sel;
  }
}
$tv->addCombiningFilter("price_range", new PricingFilter());
// $tv->addCombiningFilter("catID", "catID");




$have_filter = $tv->processFilters();

//
$tree_query = $tv->getSelectQuery();
// echo $tree_query->getSQL();



$product_selector = $tv->getRelationSelect();
// $product_selector->group_by =" sellable_products.prodID ";
// echo $product_selector->getSQL();






$view = new ListView(new SQLResultIterator($product_selector, "piID"));
$view->setCaption("Products List");

$view->setDefaultOrder(" piID DESC ");
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

echo "<div class='column left'>";

echo "<div class='categories'>";
if ($have_filter) {
    echo "<div class='tree_item'>";
    echo "<div class='tree_node_handle icon_handle_leaf'></div>";
    echo "<a  class='ActionRenderer'  href='?'>Show All Categories</a>";
    echo "</div>";
}

$tv->render();
echo "</div>";


  echo "<div class='filters'>";
  
  echo "<form method=get >";

//   if (strcmp_isset("filter","self", $_GET)) {
// 	echo "<input type='hidden' name='filter' value='self'>";
// 	echo "<input type='hidden' name='catID' value='".$_GET["catID"]."'>";
//   }
  echo "<div class='InputComponent'>";
	echo "<span class='label'>".tr("Brand").": </span>";
	$db = DBDriver::get();
	$res = $db->query("SELECT brand_name FROM sellable_products GROUP BY brand_name");
	if (!$res) throw new Exception ($db->getError());
	$arr_brands = array();
	while ($row = $db->fetch($res)) {
	  $arr_brands[] = $row["brand_name"];
	}
	$sel = new ArraySelector($arr_brands);
	
	$brands = new BrandsBean();
	$field = InputFactory::CreateField(InputFactory::SELECT, "brand_name", "Brands", 0);
	$rend = $field->getRenderer();
	$rend->setSource($sel);
	$rend->list_key = "arr_val";
	$rend->list_label = "arr_val";
	if (isset($_GET["brand_name"])) {
	  $field->setValue($db->escapeString($_GET["brand_name"]));
	}
	$rend->renderField($field);
	$db->free($res);
  echo "</div>";
  
  echo "<div class='InputComponent'>";
	echo "<span class='label'>".tr("Color").": </span>";
	$res = $db->query("SELECT distinct(color) as color FROM sellable_products WHERE color IS NOT NULL GROUP BY color");
	if (!$res) throw new Exception ($db->getError());
	$arr_colors = array();
	while ($row = $db->fetch($res)) {
	  
	  $arr_colors[] = $row["color"];
	}
	$sel1 = new ArraySelector($arr_colors);
	
	$field = InputFactory::CreateField(InputFactory::SELECT, "color", "Color", 0);
	$rend = $field->getRenderer();
	$rend->setSource($sel1);
	$rend->list_key = "arr_val";
	$rend->list_label = "arr_val";
	if (isset($_GET["color"])) {
	  $field->setValue($db->escapeString($_GET["color"]));
	}
	$rend->renderField($field); 
	$db->free($res);
  echo "</div>";
  
  echo "<div class='InputComponent'>";
	echo "<span class='label'>".tr("Size").": </span>";
	$res = $db->query("SELECT distinct(i.size_value) as size_value FROM inventory i JOIN products p ON p.prodID = i.prodID WHERE i.size_value IS NOT NULL AND p.visible = 1");
	if (!$res) throw new Exception ($db->getError());
	$arr_sizes = array();
	while ($row = $db->fetch($res)) {
	  $arr_sizes[] = $row["size_value"];
	}
	$sel2 = new ArraySelector($arr_sizes);
	
	$field = InputFactory::CreateField(InputFactory::SELECT, "size_values", "Size Values", 0);
	$rend = $field->getRenderer();
	$rend->setSource($sel2);
	$rend->list_key = "arr_val";
	$rend->list_label = "arr_val";
	if (isset($_GET["size_values"])) {
	  $field->setValue($db->escapeString($_GET["size_values"]));
	}
	$rend->renderField($field);
	$db->free($res);
  echo "</div>";
  
  
	$res = $db->query("SELECT min(price_min) as price_min, max(price_max) as price_max FROM sellable_products");
	if (!$res) throw new Exception ($db->getError());
	if ($row = $db->fetch($res)) {
		$price_min = $row["price_min"];
		$price_max = $row["price_max"];
		$value_min = $price_min;
		$value_max = $price_max;
		if (isset($_GET["price_range"])) {
		  $price_range = explode("|",$_GET["price_range"]);
		  if (count($price_range)==2) {
			$value_min = (float)$price_range[0];
			$value_max = (float)$price_range[1];
		  }
		}

		echo "<div class='InputComponent Slider'>";
		  echo "<span class='label'>".tr("Price").": </span>";
		  echo "<span class='value' id='amount'></span>";
		  echo "<div class='InputField'>";
			echo "<div class='drag' min='$price_min' max='$price_max'></div>";
			echo "<input type='hidden' name='price_range' value='$value_min|$value_max'>";
		  echo "</div>";
		echo "</div>";
	}
	
  
  ?>
<script type='text/javascript'>
addLoadEvent(function() {

    $( ".drag" ).slider({
      range: true,
      min: 0,
      max: 100,
      values: [ 0, 100 ],
      slide: function( event, ui ) {
		$(this).parents(".Slider").children(".value").html(  ui.values[ 0 ] + " - " + ui.values[ 1 ] );
		$(this).parent().children("[name='price_range']").attr("value", ui.values[0] + "|" + ui.values[1]);
      }
    });
    
    var min = Number.parseFloat($( ".drag" ).attr("min"));
    var max = Number.parseFloat($( ".drag" ).attr("max"));
    
    var value_min = min;
    var value_max = max;
    
    var price_range = $(".drag").parent().children("[name='price_range']").attr("value");
	var price_range = price_range.split("|");

	if (price_range.length==2) {
// 	  console.log(price_range.length);
	  value_min = Number.parseFloat(price_range[0]);
	  value_max = Number.parseFloat(price_range[1]);
    }
//     console.log("value-min: "+value_min);
//     console.log("value-min: "+value_max);
    
    $(".drag").slider( "option", "min",  min );
	$(".drag").slider( "option", "max",  max );
	
	$(".drag").slider( "option", "values", [ value_min, value_max ] );
	
	$(".drag").parents(".Slider").children(".value").html( value_min + " - " + value_max );
	
});
</script>
  <?php
  echo "<div class='InputComponent'>";
  echo "<input type='submit' class='DefaultButton' value='Apply'>";
  echo "<BR>";
  echo "<a class='DefaultButton' href='list.php'>Clear</a>";
  echo "</div>";
  
  echo "</form>";
  echo "</div>";

echo "</div>";

echo "<div class='column product_list'>";

$ksc->render();
$view->render();

echo "</div>";

?>

<?php
$page->finishPage();


?>
