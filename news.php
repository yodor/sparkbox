<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");

include_once("class/beans/NewsItemsBean.php");
include_once("lib/components/PublicationArchiveComponent.php");

$page = new DemoPage();
$page->addCSS(SITE_ROOT . "css/news.css");


$nb = new NewsItemsBean();
$prkey = $nb->key();

$itemID = -1;

if (isset($_GET[$prkey])) {
    $itemID = (int)$_GET[$prkey];
}

$qry = $nb->queryField($prkey,$itemID, 1);

$pac = new PublicationArchiveComponent(new NewsItemsBean(), SITE_ROOT . "news.php");


$selected_year = $pac->getYear();
$selected_month = $pac->getMonth();

if ($pac->haveSelection()) {
    $qry->select->where = " YEAR(item_date)='$selected_year' AND MONTHNAME(item_date)='$selected_month' ";
    $qry->select->order_by = " item_date DESC ";
    $qry->select->limit = "";
}

$qry->exec();


$page->startRender();


echo "<div class='news_view'>";

echo "<div class='column main'>";
while ($item_row = $qry->next()) {
    $itemID = $item_row[$nb->key()];
    trbean($itemID, "item_title", $item_row, $nb->getTableName());
    trbean($itemID, "content", $item_row, $nb->getTableName());

    echo "<div class='item_view' itemID='$itemID'>";
    echo "<div class='title'>";
    echo $item_row["item_title"];
    echo "</div>";

    echo "<div class='date'>";
    echo dateFormat($item_row["item_date"], false);
    echo "</div>";

    echo "<div class='content'>";
    $img_href = StorageItem::Image($itemID, "NewsItemsBean", 640, -1);
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


function drawLatestNews($num, $selected_year = false, $selected_month = false)
{


    global $nb;

    $qry=$nb->query();
    $qry->select->order_by = " item_date DESC";
    $qry->select->limit = "3";
    $qry->exec();

    while ($item_row = $qry->next()) {
        $itemID = $item_row[$nb->key()];
        echo "<a class='item' newsID='$itemID' href='" . SITE_ROOT . "news.php?newsID=$itemID'>";

        echo "<div class='cell image'>";
        $img_href = StorageItem::Image($itemID, $nb, 48, 48);
        echo "<div class='panel'><img src='$img_href'></div>";
        echo "</div>";

        echo "<div class='cell details'>";
        echo "<span class='title'>" . $item_row["item_title"] . "</span>";
        echo "<span class='date'>" . dateFormat($item_row["item_date"], false) . "</span>";
        echo "</div>";

        echo "</a>";
    }

}

$page->finishRender();
?>
