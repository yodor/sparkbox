<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");
include_once("lib/beans/DynamicPagePhotosBean.php");
// include_once("lib/beans/DynamicPagesBean.php");
include_once("lib/beans/MenuItemsBean.php");

// function dumpJS()
// {
//   echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/GalleryView.js'></script>";
//   echo "\n";
// }

$page = new DemoPage();


if (!isset($_GET["page_id"]) || !isset($_GET["page_class"])) {
  
  exit;
}



$page_class = $g_db->escapeString($_GET["page_class"]);
$page_id = (int)$_GET["page_id"];

try {
  @include_once("class/beans/$page_class.php");
  @include_once("lib/beans/$page_class.php");
  $b = new $page_class;

  $prkey = $b->getPrKey();
  $rrow = false;
  $num = $b->startIterator("WHERE $prkey='$page_id' LIMIT 1", " item_title, content, visible ");
  if ($num < 1) throw new Exception("This page is not available.");
  $b->fetchNext($rrow);
  if (!$rrow["visible"])throw new Exception("This page is currently unavailable.");

// width:960px;
//   height:378px;

//   $header_href =  SITE_ROOT."storage.php?cmd=image_crop&width=960&height=246&id=$page_id&class=$page_class";

//   $page->header_image_url =  $header_href;
}
catch (Exception $e)
{
  Session::set("alert", $e->getMessage());
  header("Location: ".SITE_ROOT."home.php");
  exit;
}

$menu1 = new MainMenu();
$menu1->setMenuBeanClass("MenuItemsBean");
// $parentID=0, MenuItem $parent_item = NULL, $key="menuID", $title="menu_title"
$menu1->constructMenuItems(0, NULL, "menuID", "menu_title");

$menu_bar1 = new MenuBarComponent($menu1);
$menu_bar1->setName("MenuItemsBean");


$page->beginPage();






echo "<div class='$page_class'>";

	echo "<div class='MenuBarWrapper'>";
	$menu_bar1->render();
	echo "</div>";


	echo "<div class='photo'>";
	$photo_href =  SITE_ROOT."storage.php?cmd=gallery_photo&id=$page_id&class=$page_class";

	echo "<img src='$photo_href'>";

	echo "</div>";

	echo "<div class='item_title'>".$rrow["item_title"]."</div>";

	if (isset($rrow["subtitle"])) {
	echo "<div class='subtitle'>".$rrow["subtitle"]."</div>";
	}

	echo "<div class='content'>".$rrow["content"]."</div>";

	echo "<div class='PagePhotos'>";


	  $dpp = new DynamicPagePhotosBean();
	  $num_photos = $dpp->startIterator("WHERE dpID='$page_id' LIMIT 1", " ppID, caption ");

	  if ($num_photos && $dpp->fetchNext($dpprow)) {
		$photo_id = $dpprow["ppID"];

		echo "<div class='photo_item' id='$photo_id' class='DynamicPagePhotosBean'>";
		
		  $img_href =  SITE_ROOT."storage.php?cmd=gallery_photo&id=$photo_id&class=".get_class($dpp)."";
// 		  $full_href = SITE_ROOT."storage.php?cmd=gallery_photo&id=$photo_id&class=".get_class($dpp)."";
		  
		  echo "<a class='image_popup' href='$img_href' rel='DynamicPagePhotosBean'>";
		  echo "<img src='$img_href' >";
		  echo "</a>";
		  
		  echo "<div class='caption'>";
		  echo $dpprow["caption"];
		  echo "</div>";
		echo "</div>";

	  }
	  
	echo "</div>";


echo "</div>"; //$page_class


$page->finishPage();
?>