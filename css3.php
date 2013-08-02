<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");



$page = new DemoPage();

function dumpCSS()
{
  echo '<link rel="stylesheet" href="'.SITE_ROOT.'css/css3.css" type="text/css">';
  echo "\n";
  echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/jquery.mCustomScrollbar.css'  type='text/css'>";
  echo "\n";
}

function dumpJS()
{
  echo "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js'></script>";
  echo "\n";
  
  echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/jqplugins/jquery.mousewheel.min.js'></script>";
  echo "\n";
  echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/jqplugins/jquery.mCustomScrollbar.js'></script>";
}
$page->beginPage();


// echo "<div class='gradient1 demo_custom'>";
// drawSampleText();
// echo "</div>";
// 

echo "<div class='scroll_custom'>";
drawSampleText();
echo "</div>";

?>
<script type='text/javascript'>
$(document).ready(function() {
  $(".scroll_custom").mCustomScrollbar({
        mouseWheel:true,
        scrollButtons:{
			enable:true
		}
  });
});

</script>
<?php
$page->finishPage();

function drawSampleText()
{
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";
echo "The Quick Brown Fox Jumps Over The Lazy Dog";

}

?>