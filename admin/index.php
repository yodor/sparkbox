<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

//var_dump($_COOKIE);
//exit;

$page = new AdminPage();

$page->startRender();


$page->finishRender();
?>
