<?php
include_once("session.php");
include_once("class/pages/MainPage.php");
include_once("class/beans/DynamicPagePhotosBean.php");
include_once("class/beans/DynamicPagesBean.php");

$page = new MainPage();


if (!isset($_GET["link_id"]) || !isset($_GET["link_class"])) {
  
  exit;
}



$link_class = $g_db->escapeString($_GET["link_class"]);
$link_id = (int)$_GET["link_id"];

try {
  include_once("class/beans/$link_class.php");
  $b = new $link_class;

  $prkey = $b->getPrKey();
  $rrow = false;
  $num = $b->startIterator("WHERE $prkey='$link_id' LIMIT 1", " item_title, content, visible ");
  if ($num < 1) throw new Exception("This page is not available.");
  $b->fetchNext($rrow);
  if (!$rrow["visible"])throw new Exception("This page is currently unavailable.");

// width:960px;
//   height:378px;

$header_href =  SITE_ROOT."storage.php?cmd=image_crop&width=960&height=246&id=$link_id&class=$link_class";

  $page->header_image_url =  $header_href;
}
catch (Exception $e)
{
  Session::set("alert", $e->getMessage());
  header("Location: ".SITE_ROOT."home.php");
  exit;
}



$page->beginPage();


echo "<div class='left_column'>";

echo "<span class='title blue big_text'>".$rrow["item_title"]."</span>";


$dpp = new DynamicPagePhotosBean();
$num_photos = $dpp->startIterator("WHERE dpID='$link_id' LIMIT 1", " ppID, caption ");

if ($num_photos && $dpp->fetchNext($dpprow)) {
  $photo_id = $dpprow["ppID"];

  echo "<div>";
$img_href =  SITE_ROOT."storage.php?cmd=image_crop&width=640&height=-1&id=$photo_id&class=".get_class($dpp)."";

  echo "<img style='display:block;' src='$img_href' width=640>";

  echo "<span class='image_details'>";
  echo $dpprow["caption"];
  echo "</span>";

  echo "</div>";
  echo "<BR><BR>";
}

echo $rrow["content"];

echo "</div>"; //left_column

$page->drawRightColumn();



$page->finishPage();
?>