<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");


$page = new AdminPage();

$menu = array(new MenuItem("Menu Items", "menus/index.php", "code-class.png"), new MenuItem("Gallery Photos", "photo_gallery/list.php", "code-class.png"),


              new MenuItem("Dynamic Pages", "pages/list.php", "view-financial-list.png"),


              // 	new MenuItem("Videos", "videos/list.php", "application-vnd.rn-realmedia.png"),
              new MenuItem("News", "news/list.php", "view-financial-list.png"), new MenuItem("FAQ", "faq/list.php", "view-financial-list.png"),

              // 	new MenuItem("Office Details", "offices/list.php", "view-financial-list.png"),
              // 	new MenuItem("Contact Details", "contacts/list.php", "view-financial-list.png"),
);


$page->checkAccess(ROLE_CONTENT_MENU);


$page->startRender($menu);

echo "Content Management";

$page->finishRender();
?>