<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/forms/DynamicPageInputForm.php");
include_once("lib/beans/DynamicPagesBean.php");


$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);


$bean = new DynamicPagesBean();
$bean->debug_sql = false;

$view = new InputFormView($bean, new DynamicPageInputForm());

$view->processInput();

$page->beginPage($menu);

$view->render();

$page->finishPage();


?>