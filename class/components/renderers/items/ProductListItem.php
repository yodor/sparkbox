<?php
include_once("class/beans/SellableProductsBean.php");
include_once("lib/components/renderers/items/ItemRendererImpl.php");
include_once("class/beans/ProductColorsBean.php");

class ProductListItem extends ItemRendererImpl {
  
  protected $photos = null;

  public function __construct()
  {
	 

		$this->setClassName("ProductListItem");
// 		$this->addClassName("clearfix");
		
  }
//   select 
// group_concat(piID) as items,
// group_concat(discount_amount) as discount_amounts,
// group_concat(stock_amount) as stock_amounts, 
// group_concat(price) as prices,
// group_concat(sell_price) as sell_prices,
// group_concat(color) as colors,
// group_concat(size_value) as size_values,
// group_concat(weight) as weights,
// count(piID) as item_count,
// sellable_products.*
// 
// from sellable_products GROUP BY color, prodID, catID

	public function setItem($item)
	{
		parent::setItem($item);
		$this->setAttribute("prodID", $this->item["prodID"]);
		$this->setAttribute("piID", $this->item["piID"]);
		
		
	}
	protected function renderImpl()
	{
// 		//var_dump($this->item);
// 		print_r(array_keys($this->item));
// 		echo "<HR>";
		echo "<div class='wrap'>";
		$photos_bean = ProductColorPhotosBean::class;
		if ($this->item["color_gallery"]) {
		  $photos = explode("|", $this->item["color_gallery"]);
		}
		else {
		  $photos_bean = ProductPhotosBean::class;
		  $photos = explode("|", $this->item["product_photos"]);
		}
		$same_color_pids = explode("|", $this->item["pids"]);
		
		
		$colors = array();
		if ($this->item["colors"]) {
		  $colors = explode("|", $this->item["colors"]);
		}
		
		$have_chips = explode("|", $this->item["have_chips"]);
		$color_ids = explode("|", $this->item["color_ids"]);
		$color_photo_ids = explode("|", $this->item["color_photos"]);
		$product_photo_ids = explode("|", $this->item["product_photos"]);
		
		$color_pids = explode("|", $this->item["color_pids"]);
		
		$product_href = SITE_ROOT."products/details.php?prodID={$this->item["prodID"]}";
		$item_href = SITE_ROOT."products/details.php?prodID={$this->item["prodID"]}&piID=";
		
		$item_href_main = $item_href.$this->item["piID"];
		echo "<a href='$item_href_main' class='product_link'>";
		
// 		$img_href = STORAGE_HREF."?cmd=image_crop&width=120&height=100&class=ProductPhotosBean&id=".$this->item["ppID"];

// 		if ($this->item["pclrpID"]>0) {
// 		  $img_href = STORAGE_HREF."?cmd=image_crop&width=202&height=202&class=$photos_bean&id=".$photos[0];
// 		}
  
		$img_href = STORAGE_HREF."?cmd=image_crop&width=202&height=202&class=$photos_bean&id=".$photos[0];
		
		
		
		echo "<img src='$img_href'>";

		echo "</a>";
		
		echo "<div class='product_detail'>";
		

			echo "<div class='colors_container'>";

			$num_colors = count($colors);
			if ($num_colors>0) {

				echo "<div class='colors'>".$num_colors." ".($num_colors>1 ? "colors" : "color")."</div>";
			  
				echo "<div class='color_chips'>";
				foreach ($colors as $key=>$color) {
				  //use the chip image
				  if ($have_chips[$key]>0) {
					$chip_class = ProductColorsBean::class."&bean_field=color_photo";
					$chip_id = $color_ids[$key];
				  }
				  //use first image from the color photos gallery
				  else if (isset($color_photo_ids[$key]) && $color_photo_ids[$key]>0) {
					$chip_class = ProductColorPhotosBean::class;
					$chip_id = $color_photo_ids[$key];
				  }
				  //use the first image of the product photos as color_chip
				  else {
					$chip_class = "ProductPhotossBean";
					$chip_id = $product_photo_ids[$key];
				  }
				  $href = STORAGE_HREF."?cmd=image_crop&width=48&height=48&class=$chip_class&id=$chip_id";
				  $item_href_color = $item_href.$color_pids[$key];
				  echo "<a href='$item_href_color' class='item'>";
				  echo "<img src='$href' title='{$colors[$key]}'>";
				  echo "</a>";
				}
				echo "</div>"; //color_chips
				
			}
			echo "</div>"; //colors_container
			
			echo "<div class='product_name'>".$this->item["product_name"]."</div>";
	// 		echo "<div class='product_code'><label>product_code:</label>".$this->item["product_code"]."</div>";
			

			echo "<div class='sell_price'>".sprintf("%1.2f",$this->item["sell_price"])." лв</div>";

		echo "</div>"; //product_details
		
		
		
		echo "</div>";
		
	}

	public function renderSeparator($idx_curr, $items_total) {

	}
}
?>