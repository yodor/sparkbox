<?php
//
// main module - the site root (core part, included first; store and site init.php run after it).
// The root index.php runs it (sparkbox/modules/main/index.php) and the root .htaccess sends the addresses that are
// not files or folders to it (sparkbox/modules/main/root.htaccess). A path without a path file or a script answers 404.
//
// The layers and the site add:
//  - scripts (templates/modules/main/scripts/<path>.php - the most specific one runs, Module::scriptFor): the home
//    of the site, files (logo, sitemap.xml.php), page scripts
//  - path files (templates/modules/main/path): contents in the site page
//  - redirects (Module::addRedirect): the old root scripts → their paths (301)
//
include_once("pages/SparkModulePage.php");

$module = Module::Active();
if (!$module->pageClass) $module->pageClass = SparkModulePage::class;
//public pages - no authClass
//single pages: /contacts, not /contacts/
$module->noTrailingSlash = true;

$module->addRedirect("home.php", "");
