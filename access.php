<?php
include_once("session_root.php");
include_once("class/pages/MainPage.php");

$page = new MainPage(false);

$page->beginPage();

echo tr("Access to this resource is not allowed for your account.");




$page->finishPage();


?>
