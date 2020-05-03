<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/beans/LanguagesBean.php");
include_once("lib/beans/SiteTextsBean.php");

$page = new AdminPage();
$page->checkAccess(ROLE_CONFIG_MENU);


$bean = new SiteTextsBean();

$menu = array();

$tr = new LanguagesBean();
$qry = $tr->query();
$qry->exec();
while ($row = $qry->next()) {
    $menu[] = new MenuItem("Translate For " . $row["lang_code"], "phrases.php?langID=" . $row["langID"], "applications-development-translation.png");
}


$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView($bean->query());
$view->items_per_page = 30;
$view->addColumn(new TableColumn($bean->key(), "ID"));
$view->addColumn(new TableColumn("value", "Phrase"));
$view->addColumn(new TableColumn("hash_value", "Hash Code"));
$view->addColumn(new TableColumn("actions", "Actions"));

//command actions edit/delete
$act = new ActionsTableCellRenderer();
$act->addAction($h_delete->createAction());

$view->getColumn("actions")->setCellRenderer($act);

$view->setCaption("Available Site Phrases");


$page->startRender($menu);
$page->renderPageCaption();

$view->render();
$page->finishRender();

?>
