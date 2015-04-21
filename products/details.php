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

$prodID = -1;
if (isset($_GET["prodID"])) {
  $prodID = (int)$_GET["prodID"];
}
$piID = -1;
if (isset($_GET["piID"])) {
  $piID = (int)$_GET["piID"];
}


$sp = new SellableProductsBean();
$sellable = array();
try {
  $sp->startIterator("WHERE piID='$piID' LIMIT 1");
  if ($sp->fetchNext($sellable)) {
	 $prodID = $sellable["prodID"];
  }
  else {
	throw new Exception("Unaccessible product");
  }
}
catch (Exception $e) {
  Session::set("alert", "This product is currently unaccessible.");
  header("Location: list.php");
  exit;
}

$sel = new SelectQuery();
$sel->fields = " * ";
$sel->from = " sellable_products_view ";
$sel->where = " prodID = $prodID ";



$db = DBDriver::get();

$res = $db->query($sel->getSQL());
if (!$res) throw new Exception($db->getError());

$color_thumbs = array();
$color_gallery = array();
$color_sizes = array();
$color_prices = array();
$color_pids = array();

while ($row = $db->fetch($res)) {
	
	$pids = explode("|", $row["pids"]);
	$color_name = $row["color"];
	$pclrID = $row["pclrID"];
	
	$color_pids[$pclrID] = explode("|", $row["pids"]);
	
	$color_sizes[$pclrID] = explode("|", $row["size_values"]);
	
	$color_prices[$pclrID] = explode("|", $row["sell_prices"]);
	
	if ($row["color_gallery"]) {
	  $photos = explode("|", $row["color_gallery"]);
	  foreach ($photos as $key=>$pclrpID) {
		$item = array("id"=>$pclrpID, "class"=>"ProductColorPhotosBean", "piID"=>$pids[$key], "prodID"=>$prodID, "color_name"=>$color_name);
		if (!isset($color_thumbs[$pclrID])) {
		  $color_thumbs[$pclrID] = $item;
		}
		$color_gallery[$pclrID][] = $item;

	  }
	}
	else {
	  $photos = explode("|", $row["product_photos"]);
	  foreach ($photos as $key=>$ppID) {
		$item = array("id"=>$ppID, "class"=>"ProductPhotosBean", "piID"=>$pids[$key], "prodID"=>$prodID, "color_name"=>$color_name);
		if (!isset($color_thumbs[$pclrID])) {
		  $color_thumbs[$pclrID] = $item;
		}
		$color_gallery[$pclrID][] = $item;

	  }
	}
}




$page->beginPage();
echo "<h1>".$sellable["product_name"]."</h1>";

echo "<div class='column details'>";

  echo "<div class='images'>";
	$gallery_href = STORAGE_HREF."?cmd=image_crop&width=400&height=-1";
	$big_href = STORAGE_HREF."?cmd=gallery_photo";
	echo "<div class='image_big' source='$gallery_href' >";
	foreach ($color_gallery as $pclrID=>$gallery) {
	  
	  foreach ($gallery as $key=>$item) {
		$href = $gallery_href."&class=".$item["class"]."&id=".$item["id"];
		
		echo "<a class='image_popup' href='$href' source='$big_href'><img src=''></a>";
		break;
	  }
	  break;
	}
	echo "</div>";
	echo "<div class='image_gallery'>";
	foreach ($color_gallery as $pclrID=>$gallery) {
	  echo "<div class='list' pclrID='$pclrID'>";
	  foreach ($gallery as $key=>$item) {
		$href_source = STORAGE_HREF."?cmd=image_crop&width=-1&height=128";
		$href=$href_source."&class=".$item["class"]."&id=".$item["id"];
		echo "<div class='item' bean='{$item["class"]}' itemID='{$item["id"]}' source='$href_source' onClick='javascript:changeImage(this)'>";
		echo "<img src='$href' >";
		echo "</div>";
	  }
	  echo "</div>";
	}
	echo "</div>";
  echo "</div>";
  
  echo "<div class='product_details'>";
  
	echo "<div class='price_panel'>";
	echo "<label for='sell_price'>Price:</label>";
	echo "<span class='sell_price'></span>";
	echo "</div>";
	
	echo "<HR>";
	
	echo "Colors:<BR>";
	echo "<div class='color_chooser'>";
	foreach ($color_thumbs as $key=>$item) {
	  $href = STORAGE_HREF."?cmd=image_crop&width=48&height=48&class=".$item["class"]."&id=".$item["id"];
	  $size_values = implode("|", $color_sizes[$key]);
	  $pid_values = implode("|", $color_pids[$key]);
	  $sell_prices = implode("|", $color_prices[$key]);
	  echo "<div class='color_button' pclrID='$key' piID='{$pid_values[0]}' size_values='$size_values' sell_prices='$sell_prices' pids='$pid_values' onClick='javascript:changeColor($key)'>";
	  echo "<img src='$href' title='{$item["color_name"]}'>";
	  echo "</div>";
	}
	echo "</div>";
	
	echo "<HR>";
	
	echo "Sizes:<BR>";
	
	echo "<div class='size_chooser'>";
	
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
  changeColor(pclrID);
  
  
  
});
function changeColor(pclrID) 
{
  $(".image_gallery .list .item").attr("active", "0");
  $(".color_chooser .color_button").attr("active", "0");
  
  var color_button = $(".color_chooser .color_button[pclrID='"+pclrID+"']");
  color_button.attr("active", "1");
  piID = color_button.attr("piID");
  
  var size_values = color_button.attr("size_values");
  
  var sizes = size_values.split("|");
  
//   console.log(sizes);
  var size_chooser = $(".size_chooser .product_size");
  size_chooser.empty();
  for (var a=0;a<sizes.length;a++) {
// 	console.log(sizes[a]);
	size_chooser.append("<option val='"+sizes[a]+"'>"+sizes[a]+"</option>");
  }
  
  
  
  $(".image_gallery .list").css("display", "none");
  $(".image_gallery .list[pclrID='"+pclrID+"']").css("display", "block");
  
  var first_item = $(".image_gallery .list[pclrID='"+pclrID+"'] .item").first();
  
  first_item.attr("active", "1");
  
  var bean = first_item.attr("bean");
  var id = first_item.attr("itemID");
  var href_big = $(".image_big").attr("source");
  
  
//   href_big+="&class="+bean+"&id="+id;
//   $(".image_big IMG").attr("src", href_big);
  changeImage(first_item);
  

  updatePrice();
  
}
function changeImage(elm)
{
  $(".image_gallery .list .item").attr("active", "0");
  
  var bean = $(elm).attr("bean");
  var id = $(elm).attr("itemID");
  
  var href_big = $(".image_big").attr("source");
  href_big += "&class="+bean+"&id="+id;
  
  $(".image_big IMG").attr("src", href_big);
  
  $(".image_gallery .list .item[itemID='"+$(elm).attr("itemID")+"']").attr("active", "1");
  
  var href_popup = $(".image_big A").attr("source");
  $(".image_big A").attr("href", href_popup+"&class="+bean+"&id="+id);
}
function updatePrice()
{
  console.log("Update Price");
  
  var color_chooser = $(".color_chooser .color_button[active='1']");
  var prices = color_chooser.attr("sell_prices");
  var sell_prices = prices.split("|");
  var pid_values = color_chooser.attr("pids");
  var pids = pid_values.split("|");
  
  console.log("Prices Length" + sell_prices);
  
  var size_chooser = $(".size_chooser .product_size");
  var index = size_chooser.prop("selectedIndex");
  
  console.log("SZ Index" + index);
  console.log("Price:" + sell_prices[index]);
  $(".price_panel .sell_price").html(parseFloat(sell_prices[index]).toFixed(2));
  
  var pid = pids[index];
  console.log("PID:" + pid);
  
  $(".price_panel .sell_price").attr("pid", pid);
  
}
</script>
<?php
$page->finishPage();

?>