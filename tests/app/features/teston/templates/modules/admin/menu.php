<?php
//the admin section of the feature 'teston' - after Content
include_once("pages/SparkAdminPage.php");
include_once("utils/menu/MenuItem.php");
$menu = SparkAdminPage::Menu() ?? new MenuItemList();
$menu->insertAfter("content", new MenuItem("Feature On", "fon"));
$menu->insertAfter("content", new MenuItem("Feature Off", "foff"));
SparkAdminPage::SetMenu($menu);
