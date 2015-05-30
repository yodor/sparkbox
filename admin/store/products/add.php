<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("class/forms/ProductInputForm.php");
include_once("class/beans/ProductsBean.php");


$menu=array(

);

$page = new AdminPage();
$page->checkAccess(ROLE_CONTENT_MENU);

$view = new InputFormView(new ProductsBean(), new ProductInputForm());

$view->getTransactor()->assignInsertValue("insert_date", DBDriver::get()->dateTime());

$view->processInput();

$page->beginPage($menu);

// if (!Session::get("referer",0)) {
//   Session::set("referer", $_SERVER['HTTP_REFERER']);
// }
// 
// $href = Session::get("referer");
//   
// echo "<a href='$href'>back</a>";
  

$page->renderPageCaption();

$view->render();

$page->finishPage();


?>