<?php


function drawItem($hm_id, $row=false, $special=false)
	{
// echo "<div style='display:none;'>";
// var_dump($pbrow);
// echo "</div>";

	  $id="id=scitem_$hm_id";
	  $class = "class=sc_menu_item";

	  if ($special) {

	    $class= " class=sc_menu_item_special ";

	  }
	  echo "<a  $class $id href='' onClick='javascript:return false;'>";

// 	  echo "<div class=sc_arrow_right>";
// 	  echo "</div>";
// 	
// 	  echo "<div class=sc_menu_item_title>";
// 	  echo $pbrow["title"];
// 	  echo "</div>";
// 	  echo "<div class=sc_menu_item_subtitle>";
// 	  echo $pbrow["location"];
// 	  echo "</div>";

$href = STORAGE_HREF."?cmd=image_crop&width=320&height=-1&class=HomeImagesBean&id=$hm_id";
echo "<img style='float:left;' src='$href'>";


	  echo "</a>";

	}

	function drawImageSlider()
	{
		
	
		echo "<div class=sc_menu_holder  onMouseMove='javascript:scrollMenuHorizontal(event, this)' onMouseOut='javascript:skip_advance=false' >";

$hm = new HomeImagesBean();
$num = $hm->startIterator();

$twidth = $num * 320;

		echo "<div id=menu_contents style='width:{$twidth}px;height:213px;position:absolute;left:0;top:0;z-index:2;'>";

while ($hm->fetchNext($row)) {
$id=$row[$hm->getPrKey()];
drawItem($id, $row);

}
		echo "</div>";

// 		echo "<img onLoad='javascript:fadeOut();' id=sc_image0_holder style='position:absolute;left:0;top:0;z-index:0;'>";
// 		echo "<img id=sc_image1_holder style='position:absolute;left:0;top:0;z-index:1;'>";

// 		echo "<div class=project_link_panel>";
// 		echo "<a id=project_link href='#'>Visit Project Page</a>";
// 		echo "</div>";

		// echo "<div id=scroll_pad  style='width:240px;height:300px;border:1px solid black;position:absolute;left:0;top:0;'>";
		// echo "</div>";

	      echo "</div>";
	      echo "<div class=clear></div>";

	}


// echo "<div class=images_strip>";

drawImageSlider();
?>