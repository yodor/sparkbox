<?php
include_once("session.php");


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

include_once("class/pages/ProductsPage.php");

function dumpJS()
{
  echo "<script type='text/javascript' src='".SITE_ROOT."js/product_details.js'></script>";
  echo "\n";
}
function dumpCSS()
{
  echo "<link rel='stylesheet' href='".SITE_ROOT."css/product_details.css' type='text/css'>";
  echo "\n";
}

$page = new ProductsPage();

$prodID = -1;
if (isset($_GET["prodID"])) {
  $prodID = (int)$_GET["prodID"];
}
$piID = -1;
if (isset($_GET["piID"])) {
  $piID = (int)$_GET["piID"];
}

$sellable = array();

$db = DBDriver::get();
$res = NULL;
try {

  $relation = $page->derived;
  $relation->where = " pi.prodID = $prodID ";
  
  $res = $db->query($relation->getSQL());
  if (!$res) throw new Exception("Product Not Found: ".$db->getError());

  $found_piID = false;
  while ($item = $db->fetch($res)) {
	$sellable[] = $item;
	//
	if ((int)$piID == (int)$item["piID"]) {
	  $found_piID = true;
	}
	
  }
  $db->free($res);
  
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


//per pclrID items
$color_chips = array();

//per pclrID color names
$color_names = array();

//per pclrID items
$galleries = array();

$prices = array();

$attributes = array();

$pos = 0;


foreach ($sellable as $pos=>$row) {


// 	echo $pos++;

	//NULL color links all sizes goes to key 0
	$pclrID = (int)$row["pclrID"];


	$attributes[$row["piID"]][] = array("name"=>$row["ia_name"], "value"=>$row["ia_value"]);
	
	$prices[$pclrID][$row["size_value"]][$row["piID"]] = $row["sell_price"];
	
	$color_names[$pclrID] = $row["color"];

	if (!isset($galleries[$pclrID])) {
	  $galleries[$pclrID] = array();
	  $use_photos = false;
	  
	  if ($pclrID>0) {
		$res = $db->query("SELECT pclrpID FROM product_color_photos WHERE pclrID=$pclrID ORDER BY position ASC");
		if (!$res) throw new Exception("Unable to query color gallery: ".$db->getError());
		if ($db->numRows($res)<1) $use_photos = true;
		while ($grow = $db->fetch($res)) {
		  $item = array("id"=>$grow["pclrpID"], "class"=>ProductColorPhotosBean::class);
		  $galleries[$pclrID][] = $item;
		}
		$db->free($res);
		
	  }
	  if ($use_photos || $pclrID<1) {
		//attach default photo as signle color gallery
		$res = $db->query("SELECT ppID FROM product_photos WHERE prodID=$prodID ORDER BY position ASC");
		if (!$res) throw new Exception("Unable to query product gallery: ".$db->getError());
		while ($grow = $db->fetch($res)) {
		  $item = array("id"=>$grow["ppID"], "class"=>ProductPhotosBean::class);
		  $galleries[$pclrID][] = $item;
		}
		$db->free($res);
	  }
	}
	
	$color_chips[$pclrID] = $galleries[$pclrID][0];
	if ($row["have_chip"]) {
	  $item = array("id"=>$pclrID, "class"=>ProductColorPhotosBean::class."&bean_field=color_photo");
	  $color_chips[$pclrID] = $item;
	}
	
}



$page->beginPage();

// var_dump($attributes);


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
			echo "<div class='item' bean='{$item["class"]}' itemID='{$item["id"]}' source='$href_source' onClick='javascript:changeImage(this)'>";
			$href=$href_source."&class=".$item["class"]."&id=".$item["id"];
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
	  foreach ($color_chips as $pclrID=>$item) {
		$pclrID = (int)$pclrID;
		
		$href = STORAGE_HREF."?cmd=image_crop&width=48&height=48&class=".$item["class"]."&id=".$item["id"];

		$chip_colorName = $color_names[$pclrID];

		$sizes = $prices[$pclrID];
		$pids = array();
		$sell_prices = array();
		foreach ($sizes as $size_value=>$arr) {
		  foreach($arr as $cpiID=>$sell_price) {
			$pids[] = $cpiID;
			$sell_prices[] = $sell_price;
		  }
		}
		$size_values = implode("|", array_keys($sizes));
		$cpiID = $pids[0];
		$pids = implode("|", $pids);
		$sell_prices = implode("|", $sell_prices);
		
		//sizing pids = $pid_values
		echo "<div class='color_button' pclrID='$pclrID' piID='$cpiID' size_values='$size_values' sell_prices='$sell_prices' pids='$pids' color_name='$chip_colorName'
				   onClick='javascript:changeColor($pclrID)' title='$chip_colorName'>";
	   			
// 			if ($chip_colorCode) {
// 			  echo "<div class='color_code' style='display:block;background-color:$chip_colorCode;width:48px;height:48px;'></div>";
// 			}
// 			else {
			  echo "<img src='$href' >";
// 			}
			
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
	
	echo "<HR>";
	
// 	echo "<label for='inventory_attributes'>".tr("Inventory Attributes")."</label>";
	echo "<div class='inventory_attributes'>";
	  
	echo "</div>";
	
  echo "</div>";
  
  
  
echo "</div>";




?>

<script type='text/javascript'>
var piID = <?php echo $piID;?>;

var attributes = <?php echo json_encode($attributes);?>

addLoadEvent(function(){
  
//   var first_color = $(".color_chooser .color_button").first();
//   changeColor(first_color.attr("pclrID"));
  var pclrID = $(".color_chooser .color_button[piID='"+piID+"']").attr("pclrID");
//   console.log(piID+"=>"+pclrID);

  changeColor(pclrID);

  
});

</script>

<?php
$page->finishPage();
?>