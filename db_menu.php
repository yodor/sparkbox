<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

include_once("lib/components/MenuBarComponent.php");
include_once("lib/beans/MenuItemsBean.php");


$page = new DemoPage();


$menu1 = new MainMenu();
$menu1->setMenuBeanClass("MenuItemsBean");
// $parentID=0, MenuItem $parent_item = NULL, $key="menuID", $title="menu_title"
$menu1->constructMenuItems(0, NULL, "menuID", "menu_title");

$menu_bar1 = new MenuBarComponent($menu1);
$menu_bar1->setName("MenuItemsBean");

$page->startRender();


echo "<div class='MenuBarWrapper'>";
$menu_bar1->render();
echo "</div>";


//
// // echo "<div id=debug style='height:200px;overflow:scroll;'>Debug Area</div>";

echo "<div class=clear></div>";


$page->finishRender();


?>