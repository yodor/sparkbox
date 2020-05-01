<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/beans/LanguagesBean.php");


$page = new AdminPage("Languages");
$page->checkAccess(ROLE_CONFIG_MENU);

$menu = array(// 	new MenuItem("Add Language","add.php", "list-add.png"),
              new MenuItem("Translator", "translator/list.php", "applications-development-translation.png"));

$action_add = new Action("", "add.php", array());
$action_add->setAttribute("action", "add");
$action_add->setAttribute("title", "Add Language");
$page->addAction($action_add);

$bean = new LanguagesBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);


$view = new TableView(new BeanQuery($bean));

$view->addColumn(new TableColumn($bean->key(), "ID"));
$view->addColumn(new TableColumn("lang_code", "Language Code"));
$view->addColumn(new TableColumn("language", "Language"));
$view->addColumn(new TableColumn("actions", "Actions"));

//command actions edit/delete
$act = new ActionsTableCellRenderer();
$act->addAction(new Action("Edit", "add.php", array(new ActionParameter("editID", $bean->key()))));
$act->addAction(new PipeSeparatorAction());
$act->addAction($h_delete->createAction());
$view->getColumn("actions")->setCellRenderer($act);


$view->setCaption("Languages List");


$page->startRender($menu);

$page->renderPageCaption();

$view->render();


$page->finishRender();


?>
