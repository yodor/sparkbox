<?php
//
// admin module - core part, included first (store and site init.php run after it and may override)
//
include_once("pages/SparkAdminPage.php");
include_once("auth/AdminAuthenticator.php");
include_once("features/Features.php");
include_once("templates/Template.php");

$module = Module::Active();
if (!$module->pageClass) $module->pageClass = SparkAdminPage::class;
if (!$module->authClass) $module->authClass = AdminAuthenticator::class;

//the admin pages of a feature that is off are not found (a site copy of them neither) - their menu items go too
foreach (Features::Disabled() as $feature) {
    foreach ($feature->admin as $adminPath) Template::DisablePath("/" . trim($adminPath, "/"));
}

//reachable without login - every other path redirects to login before its path file runs
$module->addPublicPath("login", "password", "password/reset");

//limited access administrators: only the top level menu sections of their roles
$module->accessCheck = function (string $path) use ($module) : bool {
    $context = $module->getAuthContext();
    if (is_null($context)) return true;

    $level = $context->getData()->get(AdminAuthenticator::SESSION_DATA_ACCESS_LEVEL);
    if (strcmp((string)$level, AdminAuthenticator::ACCESS_LIMITED) !== 0) return true;

    $section = explode("/", Module::FormatPath($path, "/", false))[0] ?? "";
    if (in_array($section, array("", "home", "login", "logout", "password"))) return true;

    $roles = $context->getData()->get(AdminAuthenticator::SESSION_DATA_ENABLED_ROLES);
    if (!is_array($roles)) $roles = array();

    $menu = SparkAdminPage::LoadMenu();
    if ($menu instanceof MenuItemList) {
        foreach ($menu->toArray() as $item) {
            if ($item instanceof MenuItem && strcmp($item->getHref(), $section) === 0) {
                return in_array($item->getName(), $roles);
            }
        }
    }
    //not a menu section - full access only
    return false;
};
