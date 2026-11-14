<?php
//
// admin module - core menu. Store and site menu.php run after it and extend
// SparkTemplateAdminPage::Menu() (prepend / append / insert) or disable paths.
//
include_once("utils/menu/MenuItem.php");

$menu = new MenuItemList();

$content = new MenuItem("Content", "content");
$menu->append($content);

$content->append(new MenuItem("Pages", "pages"));
$content->append(new MenuItem("News", "news"));
$faq = new MenuItem("FAQ", "faq");
$faq->append(new MenuItem("Sections", "sections"));
$content->append($faq);
$content->append(new MenuItem("Menus", "menus"));

$settings = new MenuItem("Settings", "settings");
$menu->append($settings);

$settings->append(new MenuItem("Admins", "admins"));
$settings->append(new MenuItem("Languages", "languages"));

SparkTemplateAdminPage::SetMenu($menu);
