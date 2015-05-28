<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

include_once("lib/components/NestedSetTreeView2.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductPhotosBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");
include_once("lib/utils/RelatedSourceFilterProcessor.php");


class ColorFilter implements IQueryFilter
{
  public function getQueryFilter($view=NULL, $value = NULL)
  {
	$sel = NULL;

	if ($value) {
	  $sel = new SelectQuery();
	  $sel->fields = "";
	  $sel->from = "";
	  if (strcmp($value, "N/A")==0 || strcmp($value, "NULL")==0) {
		$sel->where = " relation.color IS NULL ";
	  }
	  else {
		$sel->where = " relation.color='$value' ";
	  }
	}
	
	return $sel;
  }
}

class SizingFilter implements IQueryFilter
{

  public function getQueryFilter($view=NULL, $value = NULL)
  {
	$sel = NULL;
	
	if ($value) {
	  $sel = new SelectQuery();
	  $sel->fields = "";
	  $sel->from = "";
	  if (strcmp($value, "N/A")==0 || strcmp($value, "NULL")==0) {
		$sel->where = " relation.size_value IS NULL ";
	  }
	  else {
		$sel->where = " (relation.size_values LIKE '%$value|%' OR relation.size_values LIKE '%|$value%' OR relation.size_values='$value') ";
// 		$sel->where = " $related_table.size_value='$value' ";
	  }
	}
	
	return $sel;
  }
}


class PricingFilter implements IQueryFilter
{
  public function getQueryFilter($view=NULL, $value = NULL)
  {
	$sel = NULL;

	if ($value) {
	  $sel = new SelectQuery();
	  $sel->fields = "";
	  $sel->from = "";
	  
	  $price_range = explode("|", $value);
	  if (count($price_range)==2) {
		  $price_min = (float)$price_range[0];
		  $price_max = (float)$price_range[1];
		  
		  $sel->where = " (relation.sell_price >= $price_min AND relation.sell_price <= $price_max) ";
	  }
	  
	}
	return $sel;
  }
}

$page = new DemoPage();

$bean = new ProductCategoriesBean();


//construct tree view from the source bean and set tree text label field
$treeView = new NestedSetTreeView();
$treeView->setSource($bean);
$treeView->setName("demo_tree");
$treeView->open_all = false;
$treeView->list_label = "category_name";

//renderer for the tree view
$ir = new TextTreeItemRenderer();
$treeView->setItemRenderer($ir);

//construct initial relation query to aggregate with the tree view
$product_selector = new SelectQuery();
$product_selector->fields = "  ";

//color/size/price filters need not grouping! in the derived table
$derived_table = "SELECT 
pc.catID, pc.category_name, 
(SELECT GROUP_CONCAT(DISTINCT(pi1.size_value) SEPARATOR '|') FROM product_inventory pi1 WHERE pi1.prodID=pi.prodID AND (pi1.pclrID = pi.pclrID OR pi.pclrID IS NULL) GROUP BY pi.pclrID ) as size_values, 
pi.price - (pi.price * (coalesce(sp.discount_percent,0)) / 100.0) AS sell_price, 
pi.piID, pi.size_value, pi.color, pi.pclrID, p.brand_name, pi.prodID, 
p.product_code, p.product_name, p.product_description, p.keywords 
FROM product_inventory pi 
JOIN products p ON (p.prodID = pi.prodID AND p.visible=1) 
JOIN product_categories pc ON pc.catID=p.catID 
LEFT JOIN store_promos sp ON (sp.targetID = p.catID AND sp.target='Category' AND sp.start_date <= NOW() AND sp.end_date >= NOW())";

$product_selector->from = " (  $derived_table GROUP BY pi.prodID, pi.color ) as relation ";

$product_selector->where = "  ";


//process get filters
$proc = new RelatedSourceFilterProcessor("prodID");

//construct filters 
$search_fields = array("relation.product_code", "relation.product_name", "relation.product_description", "relation.keywords");
$ksc = new KeywordSearchComponent($search_fields, "relation");
$ksc->getForm()->getRenderer()->setAttribute("method", "get");

$proc->addFilter("keyword", $ksc);

$proc->addFilter("brand_name", "brand_name");
$proc->addFilter("color", new ColorFilter());
$proc->addFilter("size_value", new SizingFilter());
$proc->addFilter("price_range", new PricingFilter());

//process filters before tree select ctor
$proc->process($treeView);

$num_filters = $proc->numFilters();

//apply all filter sql to the relation
if ($num_filters) {
  $filter = $proc->getFilterAll();
//   echo "Num Filters: $num_filters";
//   echo $filter->getSQL();
  $product_selector = $product_selector->combineWith($filter);
  $treeView->open_all = true;
}
// 

//construct the aggregated tree query
$tree_selector = $bean->listTreeRelation($product_selector, "relation", "prodID", " count(relation.prodID) as related_count ");
// echo $tree_selector->getSQL();

//set the query 
$treeView->setSelectQuery($tree_selector);

$nodeID = $treeView->getSelectedID();

$product_selector->fields = " relation.* ";
$product_selector = $bean->childNodesWith($product_selector, $nodeID);
$product_selector->where.= " AND relation.catID = child.catID ";
$product_selector->group_by = " prodID, color ";
// echo $product_selector->getSQL();


$view = new TableView(new SQLResultIterator($product_selector, "prodID"));
$view->setCaption("Products List");

// $view->addColumn(new TableColumn("piID","ID"));
// $view->addColumn(new TableColumn("prodID","ID"));
$view->addColumn(new TableColumn("photo","Photo"));
$view->addColumn(new TableColumn("product_code","Product Code"));
$view->addColumn(new TableColumn("product_name","Product Name"));
$view->addColumn(new TableColumn("brand_name","Brand Name"));
$view->addColumn(new TableColumn("category_name","Category Name"));
$view->addColumn(new TableColumn("color","Color"));
$view->addColumn(new TableColumn("size_values","Sizing"));
$view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new ProductPhotosBean(), TableImageCellRenderer::RENDER_THUMB, -1, 48));
$view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);



$brand_select = new SelectQuery();
	  $brand_select->fields = " brand_name ";
	  $brand_select->from = " ($derived_table) as relation ";
	  $brand_select->group_by = " brand_name ";
	  $brand_value = $proc->applyFiltersOn($brand_select, "brand_name");
	  
$color_select = new SelectQuery();
	  $color_select->fields = " color ";
	  $color_select->from = " ($derived_table) as relation ";
	  $color_select->where = "  ";
	  $color_select->order_by = " color ";
	  $color_select->group_by = " color ";
	  $color_value = $proc->applyFiltersOn($color_select, "color");

$size_select = new SelectQuery();
	  $size_select->fields = " size_value ";
	  $size_select->from = " ($derived_table) as relation ";
	  $size_select->where = "  ";
	  $size_select->group_by = " size_value ";
	  $size_select->order_by = " prodID ";
	  $size_value = $proc->applyFiltersOn($size_select, "size_value");
	  
$price_info = array();
$price_select = new SelectQuery();
	  $price_select->fields = " min(sell_price) as price_min, max(sell_price) as price_max ";
	  $price_select->from = " ($derived_table) as relation ";	  
	  
	  //apply the other filters but skip self - slider shows always min-max of all products
	  $price_info["price_range"] = $proc->applyFiltersOn($price_select, "price_range", true);
	  
	  $db = DBDriver::get();
	  $res = $db->query($price_select->getSQL());
	  if (!$res) throw new Exception ($db->getError());
	  if ($row = $db->fetch($res)) {
		$price_info["min"] = $row["price_min"];
		$price_info["max"] = $row["price_max"];
	  }
	  $db->free($res);
	  
$page->beginPage();

// echo $product_selector->getSQL();
// echo "<HR>";

echo "<div class='column categories'>";

  echo "<div class='tree'>";
//   if ($num_filters>0) {
// 	echo "<a class='ActionRenderer Clear' href='javascript:clearFilters()'>Show All Categories</a>";
//   }
  $treeView->render();
  echo "</div>"; //tree

  echo "<BR>";
  
  echo "<div>";
  echo tr("Refine By");
  echo "<HR>";
  echo "</div>";
  
  echo "<div class='filters'>";

	echo "<div class='InputComponent'>";
	  echo "<span class='label'>".tr("Brand").": </span>";
	
	  $field = InputFactory::CreateField(InputFactory::SELECT, "brand_name", "Brands", 0);
	  $rend = $field->getRenderer();
	  $rend->setSource(ArraySelector::FromSelect($brand_select, "brand_name", "brand_name"));
	  $rend->list_key = "brand_name";
	  $rend->list_label = "brand_name";
	  $rend->setFieldAttribute("onChange", "javascript:filterChanged(this)");
	  $field->setValue($brand_value);
	  
	  $rend->renderField($field);
	echo "</div>";//InputComponent
	
	echo "<div class='InputComponent'>";
	  echo "<span class='label'>".tr("Color").": </span>";
	  $field = InputFactory::CreateField(InputFactory::SELECT, "color", "Colors", 0);
	  $rend = $field->getRenderer();
	  $rend->setSource(ArraySelector::FromSelect($color_select, "color", "color"));
	  $rend->list_key = "color";
	  $rend->list_label = "color";
	  $rend->setFieldAttribute("onChange", "javascript:filterChanged(this)");
	  $field->setValue($color_value);
	  
	  $rend->renderField($field);
	echo "</div>";//InputComponent
	
	echo "<div class='InputComponent'>";
	  echo "<span class='label'>".tr("Sizing").": </span>";
	  $field = InputFactory::CreateField(InputFactory::SELECT, "size_value", "Sizing", 0);
	  $rend = $field->getRenderer();
	  $rend->setSource(ArraySelector::FromSelect($size_select, "size_value", "size_value"));
	  $rend->list_key = "size_value";
	  $rend->list_label = "size_value";
	  $rend->setFieldAttribute("onChange", "javascript:filterChanged(this)");
	  $field->setValue($size_value);
	  
	  $rend->renderField($field);
	echo "</div>";//InputComponent
	
	echo "<div class='InputComponent Slider'>";
	  echo "<span class='label'>".tr("Price").": </span>";
	  $value_min = $price_info["min"];
	  $value_max = $price_info["max"];
	  
	  if ($price_info["price_range"]) {
		  $price_range = explode("|", trim($price_info["price_range"]));
		  if (count($price_range)==2) {
			$value_min = (float)$price_range[0];
			$value_max = (float)$price_range[1];
		  }
	  }
	 
	  echo "<span class='value' id='amount'>$value_min - $value_max</span>";
	  echo "<div class='InputField'>";
		echo "<div class='drag' min='{$price_info["min"]}' max='{$price_info["max"]}'></div>";
		echo "<input type='hidden' name='price_range' value='$value_min|$value_max'>";
	  echo "</div>";
	echo "</div>";//InputComponent
	
	echo "<button class='DefaultButton' onClick='javascript:clearFilters()'>Clear Refinements</button>";
	
  echo "</div>";//filters

echo "</div>"; //column categories

echo "<div class='column product_list'>";

  $ksc->render();
  $view->render();

echo "</div>";



  ?>
<script type='text/javascript'>
function clearFilters()
{
  var uri = new URI(document.location.href);
  
  document.location.href = uri.filename(); 
}
function filterChanged(elm, filter_name)
{
  var elm = $(elm);
  
  var name = (filter_name) ? filter_name : elm.attr("name");
  
  var value = elm.val();
  
  console.log(name+"=>"+value);
 
  var uri = new URI(document.location.href);
  uri.removeSearch(name);
  uri.addSearch(name, value);
  document.location.href = uri.toString();

}
addLoadEvent(function() {

    $( ".drag" ).slider({
      range: true,
      min: 0,
      max: 100,
      values: [ 0, 100 ],
      slide: function( event, ui ) {
		$(this).parents(".Slider").children(".value").html(  ui.values[ 0 ] + " - " + ui.values[ 1 ] );
		$(this).parent().children("[name='price_range']").attr("value", ui.values[0] + "|" + ui.values[1]);
      },
      stop: function(event, ui) {
		$(this).val(ui.values[0] + "|" + ui.values[1]);
		filterChanged(this, "price_range");
		
	  }
    });
    
    var min = Number.parseFloat($( ".drag" ).attr("min"));
    var max = Number.parseFloat($( ".drag" ).attr("max"));
    
    var value_min = min;
    var value_max = max;
    
    var price_range = $(".drag").parent().children("[name='price_range']").attr("value");
	var price_range = price_range.split("|");

	if (price_range.length==2) {
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
  
$page->finishPage();


?>
