<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/beans/DynamicPagesBean.php");
include_once("lib/components/renderers/cells/BooleanFieldCellRenderer.php");
include_once("lib/components/renderers/cells/TableImageCellRenderer.php");

$menu = array();


$page = new AdminPage();

$page->checkAccess(ROLE_CONTENT_MENU);


if (!isset($_GET["chooser"])) {
  
  $page->setCaption("Dynamic Pages");
  
  $action_add = new Action("", "add.php", array());
  $action_add->setAttribute("action", "add");
  $action_add->setAttribute("title", "Add Page");
  $page->addAction($action_add);
  
}
else {
  $page->setCaption("Choose Page to Link");

}

$page->setAccessibleTitle("Dynamic Pages");


$bean = new DynamicPagesBean();

$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);

$h_repos = new ChangePositionRequestHandler($bean);
RequestController::addRequestHandler($h_repos);





$view = new TableView(new BeanResultIterator($bean));
$view->setCaption("Dynamic Pages List");
$view->setDefaultOrder(" position ASC ");
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));
$view->addColumn(new TableColumn("photo","Photo"));
$view->addColumn(new TableColumn("item_title","Title"));
// $view->addColumn(new TableColumn("subtitle","Subtitle"));

$view->addColumn(new TableColumn("visible","Visibility"));

$view->addColumn(new TableColumn("item_date","Date"));

$view->addColumn(new TableColumn("position","Position"));

$view->addColumn(new TableColumn("actions","Actions"));

$view->getColumn("visible")->setCellRenderer(new BooleanFieldCellRenderer("Yes","No"));

$view->getColumn("photo")->setCellRenderer(new TableImageCellRenderer(new DynamicPagesBean(), IPhotoRenderer::RENDER_CROP, -1,55));
$view->getColumn("photo")->getHeaderCellRenderer()->setSortable(false);


$act = new ActionsTableCellRenderer();

if ( isset($_GET["chooser"]) && isset($_SESSION["chooser_return"]) ) {

  
  
  $action_chooser = new Action(
      "Choose", $_SESSION["chooser_return"], 
      array(
	new ActionParameter("page_id",$bean->getPrKey()), 
	new ActionParameter("page_class", "DynamicPagesBean", true)
      )
  );

  $act->addAction($action_chooser);

}
else {

    $act->addAction(
      new Action("Edit", "add.php", array(new ActionParameter("editID",$bean->getPrKey()))  )
    ); 
    $act->addAction(  new PipeSeparatorAction() );
    $act->addAction( $h_delete->createAction() );

    $act->addAction(  new RowSeparatorAction() );


    $act->addAction(
      new Action("Photo Gallery", "gallery/list.php", array(new ActionParameter($bean->getPrKey(), $bean->getPrKey())))
    );

    $act->addAction(new RowSeparatorAction());

    $bkey = $bean->getPrKey();
    $repos_param = array(new ActionParameter("item_id",$bkey));

    $act->addAction(new Action("Previous", "?cmd=reposition&type=previous", $repos_param) );
    $act->addAction(new PipeSeparatorAction());
    $act->addAction(new Action("Next", "?cmd=reposition&type=next", $repos_param) );

    $act->addAction(new RowSeparatorAction());

    $act->addAction(new Action("First", "?cmd=reposition&type=first", $repos_param) ); 
    $act->addAction(new PipeSeparatorAction());
    $act->addAction(new Action("Last", "?cmd=reposition&type=last", $repos_param) ); 



}

$view->getColumn("actions")->setCellRenderer($act);

$page->beginPage($menu);

// echo "<div class='page_caption'>";
// echo tr("Dynamic Pages");
// echo "</div>";
$page->renderPageCaption();

$view->render();
if (isset($_GET["chooser"]))unset($_GET["chooser"]);

$page->finishPage();
?>
