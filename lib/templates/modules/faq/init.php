<?php
//
// faq module - the frequently asked questions (core part, included first; store and site init.php run after it
// and may override: pageClass - the page look of the site, a frame for the contents of the paths)
//
include_once("pages/SparkPage.php");

$module = Module::Active();

//the addresses with values in them (before: the rewrite rules of the module .htaccess)
$module->addPattern("([0-9]+)(/.*)?", "section", "fqsID");
if (!$module->pageClass) $module->pageClass = SparkPage::class;
//public pages - no authClass
