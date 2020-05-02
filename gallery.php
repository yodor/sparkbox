<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");
include_once("class/beans/GalleryPhotosBean.php");


$page = new DemoPage();
$page->addCSS(SITE_ROOT . "lib/css/GalleryTape.css");
$page->addJS(SITE_ROOT . "lib/js/GalleryTape.js");
$page->addJS(SITE_ROOT . "lib/js/GalleryView.js");


$bean = new GalleryPhotosBean();
$qry = $bean->query();
$qry->select->order_by = " position ASC ";
$qry->select->fields = $bean->key();

$qry->exec();

$page->startRender();

echo "<div class='image_gallery GalleryTape'>";

echo "<div class='button left'></div>";

echo "<div class='viewport'>";
echo "<div class='slots'>";

$pos = 0;

while ($row = $qry->next()) {

    echo "<div class='slot'>";
    $itemID = $row[$bean->key()];

    $img_href = StorageItem::Image($itemID, $bean, -1, 160);
    $popup_href = StorageItem::Image($itemID, $bean);

    echo "<a class='image_popup' href='$popup_href' name='gallery_tape." . $pos . "' rel='collection2'>";
    echo "<img src='$img_href'>";
    echo "</a>";

    echo "</div>";
    $pos++;
}
echo "</div>";//viewport
echo "</div>"; //slots
echo "<div class='button right' ></div>";
echo "</div>";
?>
<script type='text/javascript'>
    onPageLoad(function () {

        var gallery_tape = new GalleryTape(".image_gallery");
        gallery_tape.connectGalleryView("collection2");

    });

</script>
<?php
$page->finishRender();
?>
