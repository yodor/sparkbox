<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");


$menu = array(new MenuItem("Main Menu", "main/list.php", "code-class.png"),);


$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$page->startRender($menu);

$page->finishRender();
?>