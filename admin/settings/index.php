<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");


$page = new AdminPage("Settings");
$page->checkAccess(ROLE_CONFIG_MENU);


$menu = array(

    new MenuItem("Administrative Users", "admins/list.php", "irc-operator.png"),
    new MenuItem("Languages", "languages/list.php", "applications-education-language.png"),
    new MenuItem("SEO", "seo.php", "applications-education-language.png"),

);


$page->startRender($menu);


$page->finishRender();

?>