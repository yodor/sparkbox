<?php
//the stylesheets / scripts of a page in one response (HTMLHead bundles) - /assets/bundle/<hash>.<css|js>?f=<files>
include_once("utils/AssetBundle.php");
if (str_starts_with((string)($_GET["route"] ?? ""), AssetBundle::ROUTE)) {
    AssetBundle::Respond((string)$_GET["route"]);
}

include_once("storage/BeanDataRequest.php");
$storage = new BeanDataRequest();
