<?php
//
// pages module - the dynamic pages (core part, included first; store and site init.php run after it and may override:
// pageClass - the page look of the site, a page with the TemplatePage trait)
//
include_once("pages/SparkModulePage.php");

$module = Module::Active();
if (!$module->pageClass) $module->pageClass = SparkModulePage::class;
//public pages - no authClass
