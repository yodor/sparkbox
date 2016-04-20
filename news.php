<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");

include_once("class/beans/NewsItemsBean.php");
include_once("lib/components/PublicationArchiveComponent.php");

$page = new DemoPage();

function dumpCSS()
{
  echo '<link rel="stylesheet" href="'.SITE_ROOT.'css/news.css" type="text/css">';
  echo "\n";
}



$nb = new NewsItemsBean();
$prkey = $nb->getPrKey();

$itemID = -1;

if (isset($_GET[$prkey])) {
  $itemID = (int)$_GET[$prkey];
}
$num = $nb->startIterator("WHERE $prkey='$itemID' LIMIT 1");


$pac = new PublicationArchiveComponent(new NewsItemsBean(), SITE_ROOT."news.php");


$selected_year = $pac->getYear();
$selected_month = $pac->getMonth();

if ($pac->haveSelection()) {
  $num = $nb->startIterator(" WHERE YEAR(item_date)='$selected_year' AND MONTHNAME(item_date)='$selected_month' ORDER BY item_date DESC");
}
if ($num < 1) {
  $num = $nb->startIterator(" WHERE YEAR(item_date)='$selected_year' AND MONTHNAME(item_date)='$selected_month' ORDER BY item_date DESC LIMIT 1 ");
}      

      
$page->beginPage();


  
echo "<div class='news_view'>";

  echo "<div class='column main'>";
    while ($nb->fetchNext($item_row)) {
      $itemID = $item_row[$nb->getPrKey()];
      trbean($itemID, "item_title", $item_row, $nb);
      trbean($itemID, "content", $item_row, $nb);

      echo "<div class='item_view' itemID='$itemID'>";
	echo "<div class='title'>";
	  echo $item_row["item_title"];
	echo "</div>";
	
	echo "<div class='date'>";
	  echo dateFormat($item_row["item_date"],false);
	echo "</div>";
	
	echo "<div class='content'>";
	  $img_href = SITE_ROOT."storage.php?cmd=image_crop&width=640&height=-1&class=NewsItemsBean&id=$itemID";
	  echo "<img src='$img_href'>";
	  echo $item_row["content"];
	echo "</div>";
	
      echo "</div>";
      
      echo "<hr>";
    }
  echo "</div>"; //column_main

  echo "<div class='column other'>";
    echo "<div class='latest'>";
      echo "<div class='caption'>";
      echo tr("Latest News");
      echo "</div>";
      drawLatestNews(3);
    
    echo "</div>";
    
    echo "<div class='archive'>";
      echo "<div class='caption'>";
      echo tr("News Archive");
      echo "</div>";
      $pac->render();
    echo "</div>";
    
  echo "</div>"; //column_other


echo "</div>";//news_view


function drawLatestNews($num, $selected_year=false, $selected_month=false)
{
  
  
  $nb = new NewsItemsBean();
  $sql = "ORDER BY item_date DESC LIMIT 3";
  
  $nb->startIterator($sql);
  
  while ($nb->fetchNext($item_row)) {
    $itemID = $item_row[$nb->getPrKey()];
    echo "<a class='item' newsID='$itemID' href='".SITE_ROOT."news.php?newsID=$itemID'>";
    
      echo "<div class='cell image'>";
      $img_href = SITE_ROOT."storage.php?cmd=image_thumb&width=48&height=48&class=NewsItemsBean&id=$itemID";
      echo "<div class='panel'><img src='$img_href'></div>";
      echo "</div>";
      
      echo "<div class='cell details'>";
	echo "<span class='title'>".$item_row["item_title"]."</span>";
	echo "<span class='date'>".dateFormat($item_row["item_date"], false)."</span>";
      echo "</div>";
    
    echo "</a>";
  }
  
}

$page->finishPage();
?>
