<?php
include_once("session.php");

include_once("class/pages/ProductsPage.php");

include_once("lib/components/NestedSetTreeView2.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductPhotosBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLResultIterator.php");
include_once("lib/utils/RelatedSourceFilterProcessor.php");
include_once("class/beans/ProductColorPhotosBean.php");
include_once("class/utils/filters/ProductFilters.php");

include_once("class/components/renderers/items/ProductListItem.php");
include_once("class/components/renderers/cells/ProductPhotoCellRenderer.php");

function dumpCSS()
{
  echo "<link rel='stylesheet' href='".SITE_ROOT."css/related_tree.css' type='text/css'>";
  echo "\n";
}


$page = new ProductsPage();

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

$inventory_selector = new SelectQuery();
$inventory_selector->fields = "  ";

//color/size/price filters need NOT grouping! in the derived table
$derived = clone $page->derived;
$derived->group_by = " pi.prodID, pi.color ";

// $product_selector->from = " (  $derived_table GROUP BY pi.prodID, pi.color ) as relation ";
$product_selector->from = " ( ".$derived->getSQL(false, false)." ) as relation ";
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
$proc->addFilter("ia", new InventoryAttributeFilter());

//process filters before tree select ctor
$proc->process($treeView);

$num_filters = $proc->numFilters();

//apply all filter sql to the relation 
if ($num_filters) {
  $filter = $proc->getFilterAll();
//   echo "Num Filters: $num_filters";
  
  $product_selector = $product_selector->combineWith($filter);

  $treeView->open_all = true;
  
//   $inventory_selector = $inventory_selector->combineWith($filter);
  
}
// 

//construct the aggregated tree query
$tree_selector = $bean->listTreeRelation($product_selector, "relation", "prodID", " count(relation.prodID) as related_count ");
// echo $tree_selector->getSQL();

//set the query 
$treeView->setSelectQuery($tree_selector);

$nodeID = $treeView->getSelectedID();

$product_selector->fields = " relation.* "; //TODO list only needed fields here?
$product_selector = $bean->childNodesWith($product_selector, $nodeID);
$product_selector->where.= " AND relation.catID = child.catID ";
$product_selector->group_by = " relation.prodID, relation.color ";



if (strcmp_isset("view", "list", $_GET)) {
  $view = new TableView(new SQLResultIterator($product_selector, "piID"));
  // $view->addColumn(new TableColumn("piID","ID"));
  // $view->addColumn(new TableColumn("prodID","ID"));
  $view->addColumn(new TableColumn("pclrpID","Photo"));
  $view->addColumn(new TableColumn("product_code","Product Code"));
  $view->addColumn(new TableColumn("product_name","Product Name"));
  $view->addColumn(new TableColumn("brand_name","Brand Name"));
  // $view->addColumn(new TableColumn("category_name","Category Name"));
  $view->addColumn(new TableColumn("color","Color"));
  $view->addColumn(new TableColumn("sell_price","Price"));
  // $view->addColumn(new TableColumn("size_values","Sizing"));
  $view->addColumn(new TableColumn("colors","Colors"));
  $view->addColumn(new TableColumn("color_ids","Colors"));
  
  $view->getColumn("pclrpID")->setCellRenderer(new ProductPhotoCellRenderer(TableImageCellRenderer::RENDER_THUMB, -1, 48));
  $view->getColumn("pclrpID")->getHeaderCellRenderer()->setSortable(false);
}
else {
  $view = new ListView(new SQLResultIterator($product_selector, "piID"));
  $view->setItemRenderer(new ProductListItem());
}
$view->items_per_page = 12;

$sort_price = new PaginatorSortField("relation.sell_price", "Price");
$view->getPaginator()->addSortField($sort_price);
$sort_prod = new PaginatorSortField("relation.prodID", "Auto");
$view->getPaginator()->addSortField($sort_prod);
$view->getTopPaginator()->view_modes_enabled = true;
// $view->setCaption("Products List");

$derived = clone $page->derived;

$derived_table = $derived->getSQL(false,false);

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

//dinamic filters
	  $dyn_filters = array();
	  try {

		$ia_name_select = new SelectQuery(); //clone $inventory_selector;
		$ia_name_select->fields = "  ";
		$ia_name_select->from = " ($derived_table) as relation  ";
		$ia_name_select->where = "   ";
		
		$proc->applyFiltersOn($ia_name_select, "ia", true);
		
		$ia_name_select->fields = " distinct(relation.ia_name) as ia_name ";
		$ia_name_select->combineSection("where", "  relation.ia_name  IS NOT NULL");
// 		echo $ia_name_select->getSQL();
// // 
		$res = $db->query($ia_name_select->getSQL());
		if (!$res) throw new Exception ("Unable to query inventory attributes: ".$db->getError());
		while ($row = $db->fetch($res)) {
		  $name = $row["ia_name"];
		  $sel = new SelectQuery();
		  $sel->fields = "  ";
		  $sel->from = " ($derived_table) as relation  ";
		 
		  $value = $proc->applyFiltersOn($sel, "ia");
		  
		  $sel->fields = " distinct(relation.ia_value) as ia_value ";
		  $sel->combineSection("where", "  relation.ia_name = '$name' AND relation.ia_value > ''");
		  $sel->order_by = " CAST(relation.ia_value AS DECIMAL(10,2)) ";
		  
// 		  echo $sel->getSQL()."<HR>";

		  //parse value into name pairs - ia=Материал:1|Години:1
		  if ($value) {
			$ia_values = explode("|", $value);
			if (count($ia_values)>0) {
			  foreach ($ia_values as $pos=>$filter_value) {
				  if (!$filter_value) continue;
				  $group = explode(":", $filter_value);
				  if (is_array($group) && count($group)==2) {
					if (strcmp($name,$group[0])==0) {
						$value = $group[1];
					}
				  }
				  
			  }
			}
		  }
		  $dyn_filters[$name] = array("select"=>$sel, "value"=>$value);
		}
	  }
	  catch (Exception $e) {
// 		echo $ia_name_select->getSQL();
// 		echo $product_selector->getSQL();
		
	  }
	  if (is_resource($res)) $db->free($res);

	  
$page->beginPage();
// 

// echo $product_selector->getSQL();
// echo $attributes_select->getSQL();
// echo "<HR>";

echo "<div class='column categories'>";

  echo "<div class='tree'>";
//   if ($num_filters>0) {
// 	echo "<a class='ActionRenderer Clear' href='javascript:clearFilters()'>Show All Categories</a>";
//   }
  $treeView->render();
  echo "</div>"; //tree

  echo "<BR>";
  
//   echo "<div>";
//   echo tr("Refine By");
//   echo "<HR>";
//   echo "</div>";
  
  echo "<div class='filters'>";
	echo "<form name='filters' autocomplete='off'>";
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
	 
	 $value_min = sprintf("%1.2f", $value_min);
	 $value_max = sprintf("%1.2f", $value_max);
	 
	  echo "<span class='value' id='amount'>$value_min - $value_max</span>";
	  echo "<div class='InputField'>";
		echo "<div class='drag' min='{$price_info["min"]}' max='{$price_info["max"]}'></div>";
		echo "<input type='hidden' name='price_range' value='$value_min|$value_max'>";
	  echo "</div>";
	echo "</div>";//InputComponent
	
	try {
	  foreach($dyn_filters as $name=>$item) {
		echo "<div class='InputComponent'>";
		  echo "<span class='label'>".tr($name).": </span>";
		  $field = InputFactory::CreateField(InputFactory::SELECT, "$name", "$name", 0);
		  $rend = $field->getRenderer();
		  $sel = $item["select"];
  // 		echo $sel->getSQL();
		  $rend->setSource(ArraySelector::FromSelect($item["select"], "ia_value", "ia_value"));
		  $rend->list_key = "ia_value";
		  $rend->list_label = "ia_value";
		  $rend->setFieldAttribute("onChange", "javascript:filterChanged(this, 'ia', true)");
		  $rend->setFieldAttribute("filter_group", "ia");
		  $field->setValue($item["value"]);
		  
		  $rend->renderField($field);
		echo "</div>";//InputComponent
	  }
	}
	catch (Exception $e) {
	  echo $e;
	}
	
	
	echo "</form>";
	
	echo "<button class='DefaultButton' onClick='javascript:clearFilters()'>Clear Refinements</button>";
	
  echo "</div>";//filters

echo "</div>"; //column categories

echo "<div class='column product_list'>";

  $ksc->render();
  echo "<div class='clear'></div>";
//   $view->enablePaginators(false);
  $view->render();

echo "</div>";



  ?>
<script type='text/javascript'>
function clearFilters()
{
  var uri = new URI(document.location.href);
//   console.log(uri.filename());
  document.location.href = uri.filename(); 
}
function filterChanged(elm, filter_name, is_combined)
{
  var elm = $(elm);
  
  var name = (filter_name) ? filter_name : elm.attr("name");
  
  var value = elm.val();
  
  console.log(name+"=>"+value);
 
  
  
  if (is_combined) {
	value = elm.attr("name")+":"+value;
	$("[filter_group='"+filter_name).each(function(idx){
		var val = $(this).val();
		if (val) {
		  value+="|"+$(this).attr("name")+":"+val;
		}
	});
  }
  
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
		var min = parseFloat(ui.values[0]).toFixed(2);
		var max = parseFloat(ui.values[1]).toFixed(2);
		$(this).parents(".Slider").children(".value").html( min + " - " + max );
		$(this).parent().children("[name='price_range']").attr("value", min + "|" + max);
      },
      stop: function(event, ui) {
		var min = parseFloat(ui.values[0]).toFixed(2);
		var max = parseFloat(ui.values[1]).toFixed(2);
		$(this).val(min + "|" + max);
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
