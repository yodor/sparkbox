<?php
//test module: the module page for its paths, a page class of its own for 'item' (and below), an old address
include_once("pages/SparkPage.php");

$module = Module::Active();
$module->pageClass = SparkPage::class;
$module->addPagePath("item", "RouteTestPage", "class/pages/RouteTestPage.php");
$module->addRedirect("list.php", "");
$module->addRedirect("item.php", "item");
