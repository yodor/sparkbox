<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");

include_once("class/beans/GalleryPhotosBean.php");

function dumpCSS()
{
  echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/GalleryTape.css'>";
}
function dumpJS()
{
  echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/GalleryTape.js'></script>";
  echo "\n";
}

$page = new DemoPage();


$page->beginPage();


$bean = new GalleryPhotosBean();
$bean->startIterator(" WHERE 1 ", $bean->getPrKey());



echo "<div class='custom_gallery GalleryTape'>";
  
  echo "<div class='button left'></div>";
  
  echo "<div class='viewport'>";
  echo "<div class='slots'>";

  while ($bean->fetchNext($row)) {

    echo "<div class='slot'>";
    $itemID = $row[$bean->getPrKey()];

    $img_href = SITE_ROOT."storage.php?cmd=image_crop&width=-1&height=160&class=GalleryPhotosBean&id=$itemID";//&skip_cache=1
    $popup_href = SITE_ROOT."storage.php?cmd=gallery_photo&class=GalleryPhotosBean&id=$itemID";

    echo "<a class='image_popup' href='$popup_href' rel='collection1'>";
    echo "<img src='$img_href'>";
    echo "</a>";
    
    echo "</div>";

  }
  echo "</div>";//viewport
  echo "</div>"; //slots
  echo "<div class='button right' ></div>";
echo "</div>";
?>
<script type='text/javascript'>
addLoadEvent(function(){

  var gallery_tape = new GalleryTape(".custom_gallery");
  gallery_tape.connectGalleryView("collection1");

});

</script>
<?php
?>
<style>

.ImagePopupPanel .Button[action="CloseImagePopup"] {
  left:100%;
  top:0px;
  width:20px;
  height:20px;
  margin-left:-30px;
  margin-top:-30px;
}
.ImagePopupPanel .Contents {
  padding:0px;
  box-sizing:border-box;
  position:relative;
  -moz-box-sizing:border-box;
}
.top_frame {
  position:absolute;
  left:0px;
  top:0px;
  width:100%;
  height:100%;
  border:10px solid rgba(200,200,200,0.5);
   box-sizing:border-box;
   -moz-box-sizing:border-box;
}
</style>
<script type='text/javascript'>
GalleryView.prototype.processPopupContents = function(html)
{

  var contents = $(html);
  contents.find(".Inner .Contents").prepend("<a class='Button' action='CloseImagePopup'>X</a>");
  
  contents.find(".Inner .Contents").append("<div class='top_frame'></div>");
  
  var result = contents.get(0).outerHTML;

  return result;
}

</script>
<?php
$page->finishPage();
?>