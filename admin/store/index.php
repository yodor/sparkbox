<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");







$page = new AdminPage();

$menu=array(
	new MenuItem("Attributes", "attributes/list.php", "class:store attributes"),
	new MenuItem("Classes", "classes/list.php", "class:store_classes"),
    new MenuItem("Categories", "categories/list.php", "class:store categories"),
    new MenuItem("Colors", "colors/list.php", "class:store colors"),
    new MenuItem("Sizes", "sizes/list.php?prodID", "class:store sizes"),
	new MenuItem("Products", "products/list.php", "class:store products"),
  
);


$page->checkAccess(ROLE_CONTENT_MENU);


$page->beginPage($menu);

echo "Store Management";

$page->finishPage();
?>