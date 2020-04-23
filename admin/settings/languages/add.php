<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");

include_once("lib/beans/LanguagesBean.php");
include_once("lib/forms/LanguageInputForm.php");

$menu = array();

$page = new AdminPage("Add Language");
$page->checkAccess(ROLE_CONFIG_MENU);

$view = new InputFormView(new LanguagesBean(), new LanguageInputForm());

$view->processInput();

$page->startRender($menu);

$view->render();

$page->finishRender();


?>