<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");



$page = new AdminPage();

$page->startRender();


$page->finishRender();
?>
