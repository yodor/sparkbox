<?php
//
// admin module - core menu. Store and site menu.php run after it and extend
// SparkTemplateAdminPage::Menu() (prepend / append / insert) or disable paths.
//
include_once("utils/menu/MenuItem.php");

$menu = new MenuItemList();

$content = new MenuItem("Content", "content");
$menu->append($content);

$settings = new MenuItem("Settings", "settings");
$menu->append($settings);

$settings->append(new MenuItem("Admins", "admins"));
$settings->append(new MenuItem("Languages", "languages"));

SparkTemplateAdminPage::SetMenu($menu);
