<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

include_once("lib/components/MenuBarComponent.php");
include_once("class/beans/ProductCategoriesBean.php");


function constructSubmenu($item, $level, $max_items, $max_level)
{
    $level++;
    $max_items--;

    if ($level > $max_level) return;

    for ($b = 0; $b < $max_items; $b++) {
        $sitem = new MenuItem("$b.MenuItem (Level: $level)", "menu.php?branch=$b&level=$level");
        $item->addMenuItem($sitem);
        constructSubmenu($sitem, $level, $max_items, $max_level);

    }

}


$page = new DemoPage();

$menu = new MainMenu();

$menu->setName("ConstructedMenu");

$arr = array();
for ($a = 0; $a < 1; $a++) {
    $item = new MenuItem("MenuItem " . ($a + 1), "menu.php");

    if ($a < 2) {
        constructSubmenu($item, 0, 6, 3);
    }
    $arr[] = $item;
}

$menu->setMenuItems($arr);

$menu_bar = new MenuBarComponent($menu);
$menu_bar->setName("ConstructedMenu");

$menu_bar->getMainMenu()->findMenuIndex();

$menu1 = new MainMenu();
$menu1->setMenuBeanClass("ProductCategoriesBean");
// $parentID=0, MenuItem $parent_item = NULL, $key="menuID", $title="menu_title"
$menu1->constructMenuItems(0, NULL, "catID", "category_name");

$menu_bar1 = new MenuBarComponent($menu1);
$menu_bar1->setName("ProductCategoriesBean");

$page->startRender();

$menu_bar->render();

echo "<BR><BR><BR><BR><BR><BR><BR><BR>";


$menu_bar1->render();

echo "<div class=clear></div>";

$page->finishRender();

?>