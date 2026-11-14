<?php
//
// admin module - core part, included first (store and site init.php run after it and may override)
//
include_once("pages/SparkTemplateAdminPage.php");
include_once("auth/AdminAuthenticator.php");

$module = Module::Active();
if (!$module->pageClass) $module->pageClass = SparkTemplateAdminPage::class;
if (!$module->authClass) $module->authClass = AdminAuthenticator::class;

//reachable without login - every other path redirects to login before its path file runs
$module->addPublicPath("login", "password");
