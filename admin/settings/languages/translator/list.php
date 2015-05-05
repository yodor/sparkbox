<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/beans/LanguagesBean.php");
include_once("lib/beans/SiteTextsBean.php");

$page = new AdminPage();
$page->checkAccess(ROLE_CONFIG_MENU);


$bean = new SiteTextsBean();

$menu=array();

$tr = new LanguagesBean();
$tr->startIterator();

while ($tr->fetchNext($row)){
	$menu[] = new MenuItem("Translate For ".$row["lang_code"], "phrases.php?langID=".$row["langID"],"applications-development-translation.png");
}


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);




$view = new TableView( new BeanResultIterator($bean));
$view->items_per_page = 30;
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));
$view->addColumn(new TableColumn("value","Phrase"));
$view->addColumn(new TableColumn("hash_value","Hash Code"));
$view->addColumn(new TableColumn("actions","Actions"));

//command actions edit/delete
$act = new ActionsTableCellRenderer();
$act->addAction( $h_delete->createAction() );

$view->getColumn("actions")->setCellRenderer($act);

$view->setCaption("Available Site Phrases");



$page->beginPage($menu);
$page->renderPageCaption();

$view->render();
$page->finishPage();

?>
