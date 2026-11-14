<?php
//a page with local and external assets - checked by core/versions.test.php
include_once("pages/SparkPage.php");
$page = new SparkPage();
$page->head()->addCSS("https://fonts.example.com/css?family=A:wght@400;700&display=swap");
$page->startRender();
$page->finishRender();
