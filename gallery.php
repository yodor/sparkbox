<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");

include_once("class/beans/GalleryPhotosBean.php");

function dumpCSS()
{
    echo "<link rel='stylesheet' href='" . SITE_ROOT . "lib/css/GalleryTape.css'>";
}

function dumpJS()
{
    echo "<script type='text/javascript' src='" . SITE_ROOT . "lib/js/GalleryTape.js'></script>";
    echo "<script type='text/javascript' src='" . SITE_ROOT . "lib/js/GalleryView.js'></script>";
    echo "\n";
}

$page = new DemoPage();


$page->startRender();


$bean = new GalleryPhotosBean();
$bean->startIterator(" WHERE 1 ORDER BY position ASC ", $bean->key());


echo "<div class='image_gallery GalleryTape'>";

echo "<div class='button left'></div>";

echo "<div class='viewport'>";
echo "<div class='slots'>";

$pos = 0;

while ($bean->fetchNext($row)) {

    echo "<div class='slot'>";
    $itemID = $row[$bean->key()];

    $img_href = SITE_ROOT . "storage.php?cmd=image_crop&width=-1&height=160&class=GalleryPhotosBean&id=$itemID";//&skip_cache=1
    $popup_href = SITE_ROOT . "storage.php?cmd=gallery_photo&class=GalleryPhotosBean&id=$itemID";

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
