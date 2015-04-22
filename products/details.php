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


//cart list
// SELECT 
// (select pp.ppID FROM product_photos pp WHERE pp.prodID=sp.prodID ORDER BY position ASC LIMIT 1) as product_photo,
// (select pcp.pclrpID FROM product_color_photos pcp WHERE pcp.pclrID=sp.pclrID ORDER BY position ASC LIMIT 1) as color_photo,
// sp.*
// FROM `sellable_products` sp 

function dumpJS()
{
  echo "<script type='text/javascript' src='".SITE_ROOT."js/product_details.js'></script>";
  echo "\n";
}


$page = new DemoPage();

$prodID = -1;
if (isset($_GET["prodID"])) {
  $prodID = (int)$_GET["prodID"];
}
$piID = -1;
if (isset($_GET["piID"])) {
  $piID = (int)$_GET["piID"];
}

$sellable = array();

try {
  $sp = new SellableProductsView();

  $num = $sp->startIterator("WHERE prodID='$prodID' ");
  
  $found_piID = false;
  while ($sp->fetchNext($item)) {
	$sellable[] = $item;
	//
	if ((int)$piID == (int)$item["piID"]) {
	  $found_piID = true;
	}
	
  }

  if (count($sellable)<1) {
	throw new Exception("Product Not Found.");
  }
  
  if (!$found_piID) {
	$piID = $sellable[0]["piID"];
  }
  
}
catch (Exception $e) {
  Session::set("alert", "This product is currently unaccessible. Error: ".$e->getMessage());
  header("Location: list.php");
  exit;
}

// $sel = new SelectQuery();
// $sel->fields = " * ";
// $sel->from = " sellable_products_view ";
// $sel->where = " prodID = '$prodID'   ";



// $db = DBDriver::get();
// 
// $res = $db->query($sel->getSQL());
// if (!$res) throw new Exception($db->getError());

//chips for all colors
$chips = array();

//color names
$colors = array();

//per pclrID items
$galleries = array();
$sizes = array();
$prices = array();
$color_pids = array();


$process_color_chips = true;


$pos = 0;


foreach ($sellable as $pos=>$row) {

// 	echo $pos++;
	
	$color_name = $row["color"];
	
	$pclrID = (int)$row["pclrID"];

	
	//pids from the same color
	$color_pids[$pclrID] = array();

	$color_pids[$pclrID] = $row["pids"] ? explode("|", $row["pids"]) : array($piID);
	if (in_array($piID, $color_pids[$pclrID])) {
	  $piID = $color_pids[$pclrID][0];
	}

	
	
	
	
	$sizes[$pclrID] = $row["size_values"] ? explode("|", $row["size_values"]) :  array($row["size_value"]);
	$prices[$pclrID] = $row["sell_prices"] ? explode("|", $row["sell_prices"]) : array($row["sell_price"]);
	
	//construct photo galleries
	$item_class = "";
	$photos = array();
	if ($row["color_gallery"]) {
	  $photos = explode("|", $row["color_gallery"]);
	  $item_class = ProductColorPhotosBean::class;
	}
	else {
	  $photos = explode("|", $row["product_photos"]);
	  $item_class = ProductPhotosBean::class;
	}
	foreach ($photos as $idx=>$id) {
		$item = array("id"=>$id, "class"=>$item_class);
		$galleries[$pclrID][] = $item;
	}
	
	
	
	//

	
	if ($process_color_chips ) {
	  $colors = explode("|", $row["colors"]);
	  $have_chips = explode("|", $row["have_chips"]);
	  $color_ids = explode("|", $row["color_ids"]);
	  $color_photo_ids = explode("|", $row["color_photos"]);
	  $product_photo_ids = explode("|", $row["product_photos"]);
	  $same_color_pids = explode("|", $row["color_pids"]);
	  
	  foreach ($colors as $idx=>$color) {
		  $pclrID = $color_ids[$idx];
		  //use the chip image
		  if ($have_chips[$idx]>0) {
			$chip_class = "ProductColorsBean&bean_field=color_photo";
			$chip_id = $color_ids[$idx];
		  }
		  //use first image from the color photos gallery
		  else if (isset($color_photo_ids[$idx]) && $color_photo_ids[$idx]>0) {
			$chip_class = "ProductColorPhotosBean";
			$chip_id = $color_photo_ids[$idx];
		  }
		  //use the first image of the product photos as color_chip
		  else {
			$chip_class = "ProductPhotosBean";
			$chip_id = $product_photo_ids[$idx];
		  }
		  
		  $item = array("id"=>$chip_id, "class"=>$chip_class, "piID"=>$same_color_pids[$idx], "prodID"=>$prodID, "color_name"=>$color);
		  $chips[$pclrID] = $item;
	  }
	  $process_color_chips = false;
	}
}



$page->beginPage();




$sellable_variation = $sellable[0];
// echo $sel->getSQL();

echo "<h1>".$sellable_variation["product_name"]."</h1>";

echo "<div class='column details'>";

  echo "<div class='images'>";
  
	//main image
	$gallery_href = STORAGE_HREF."?cmd=image_crop&width=400&height=-1";
	$big_href = STORAGE_HREF."?cmd=gallery_photo";
	echo "<div class='image_big' source='$gallery_href' >";
	echo "<a class='image_popup' href='' source='$big_href'><img src='$big_href'></a>";
	echo "</div>";
	
	//photo galleries per color
	echo "<div class='image_gallery'>";
	  foreach ($galleries as $pclrID=>$gallery) {
		echo "<div class='list' pclrID='$pclrID'>";
		  foreach ($gallery as $key=>$item) {
			$href_source = STORAGE_HREF."?cmd=image_crop&width=110&height=110";
			$href=$href_source."&class=".$item["class"]."&id=".$item["id"];
			echo "<div class='item' bean='{$item["class"]}' itemID='{$item["id"]}' source='$href_source' onClick='javascript:changeImage(this)'>";
			echo "<img src='$href' >";
			echo "</div>";
		  }
		echo "</div>";//list
	  }
	echo "</div>";//image_gallery
	
  echo "</div>"; // images
  
  echo "<div class='product_details'>";
  
	echo "<div class='price_panel'>";
	  echo "<label for='sell_price'>Price:</label>";
	  echo "<span class='sell_price' piID='$piID'>{$sellable_variation["sell_price"]}</span>";
	echo "</div>";
	
	echo "<HR>";
	
	
	
	
	echo "<div class='colors_panel'>";
	  echo tr("Color").":<span class='current_color'></span>";
	  echo "<div class='color_chooser'>";
	  foreach ($chips as $pclrID=>$item) {
		$pclrID = (int)$pclrID;
		
		$href = STORAGE_HREF."?cmd=image_crop&width=48&height=48&class=".$item["class"]."&id=".$item["id"];
		$size_values = implode("|", $sizes[$pclrID]);
		$pid_values = implode("|", $color_pids[$pclrID]);
		$sell_prices = implode("|", $prices[$pclrID]);
		$chip_piID = isset($color_pids[$pclrID][0]) ? $color_pids[$pclrID][0] : $piID;

		$chip_colorName = $item["color_name"];
		
		//sizing pids = $pid_values
		echo "<div class='color_button' pclrID='$pclrID' piID='$chip_piID' size_values='$size_values' sell_prices='$sell_prices' pids='$pid_values' color_name='$chip_colorName'
				   onClick='javascript:changeColor($pclrID)'>";
		echo "<img src='$href' title='$chip_colorName'>";
		echo "</div>";
	  }
	  echo "</div>";//color_chooser
	echo "</div>";//colors_panel
	
	
	echo "<HR>";
	
	
	//default hidden
	echo "<div class='size_chooser'>";
	  echo "<label for='product_size'>".tr("Size").":</label>";
	  echo "<select class='product_size' onChange='javascript:updatePrice()'>";
	  echo "</select>";
	echo "</div>";
	
  echo "</div>";
  
  
  
echo "</div>";




?>

<script type='text/javascript'>
var piID = <?php echo $piID;?>;


addLoadEvent(function(){
  
//   var first_color = $(".color_chooser .color_button").first();
//   changeColor(first_color.attr("pclrID"));
  var pclrID = $(".color_chooser .color_button[piID='"+piID+"']").attr("pclrID");
//   if (pclrID>0) {
	changeColor(pclrID);
//   }
  
});

</script>

<?php
$page->finishPage();
?>