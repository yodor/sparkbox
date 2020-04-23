<?php
include_once("session.php");

include_once("class/pages/DemoPage.php");


$page = new DemoPage();

function dumpCSS()
{
    echo '<link rel="stylesheet" href="' . SITE_ROOT . 'css/fonts.css" type="text/css">';
    echo "\n";
}

$page->startRender();


echo "<div class='container arial'>";
drawSampleText();
echo "</div>";

// echo "<HR>";

echo "<div class='container custom'>";
drawSampleText();
echo "</div>";

// echo "<HR>";

echo "<div class='container gothic'>";
drawSampleText();
echo "</div>";

echo "<HR>";
echo "<div class='container'>";
echo "<div class='dropshadow dropcnt'><div class='image boxshadow'></div>Печатни Рекламни материали</div>";
echo "<div class='hr'></div>";
echo "<div class='dropshadow dropcnt'>Екстериорни Рекламни елементи</div>";
echo "<div class='hr'></div>";
echo "<div class='dropshadow dropcnt'>Широкоформатен печат</div>";
echo "<div class='hr'></div>";
$page->finishRender();

function drawSampleText()
{
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";
    echo "The Quick Brown Fox Jumps Over The Lazy Dog. ";

}

?>
