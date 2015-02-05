<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/beans/AdminUsersBean.php");
include_once("lib/components/renderers/cells/CallbackTableCellRenderer.php");

include_once("lib/handlers/DeleteItemRequestHandler.php");
include_once("lib/handlers/ToggleFieldRequestHandler.php");

$menu=array(
  new MenuItem("Add Admin","add.php", "list-add.png"),
);

$page = new AdminPage();
$page->checkAccess(ROLE_ADMIN_USERS_MENU);



$bean = new AdminUsersBean();
$h_delete = new DeleteItemRequestHandler($bean);
RequestController::addRequestHandler($h_delete);
$h_toggle = new ToggleFieldRequestHandler($bean);
RequestController::addRequestHandler($h_toggle);




$view = new TableView(new BeanResultIterator($bean));
// $view->search_filter = " ORDER BY day_num ASC ";
$view->addColumn(new TableColumn($bean->getPrKey(),"ID"));
$view->addColumn(new TableColumn("email","Email"));
$view->addColumn(new TableColumn("fullname","Full Name"));
$view->addColumn(new TableColumn("date_created","Date Created"));
$view->addColumn(new TableColumn("last_active","Last Active"));
$view->addColumn(new TableColumn("access_level","Access"));
$view->addColumn(new TableColumn("counter","Login Count"));
$view->addColumn(new TableColumn("status","Availability"));
$view->addColumn(new TableColumn("actions","Actions"));

$view->getColumn("access_level")->setCellRenderer(new CallbackTableCellRenderer("draw_access_level"));

$act = new ActionsTableCellRenderer();
$act->addAction(
  new Action("Edit", "add.php", array(new ActionParameter("editID",$bean->getPrKey()))  )
); 
$act->addAction(new PipeSeparatorAction());
$act->addAction( $h_delete->createAction() );

$view->getColumn("actions")->setCellRenderer($act);

$vis_act = new ActionsTableCellRenderer();
$vis_act->addAction( $h_toggle->createAction("Disable", "&field=suspend&status=1", "return (\$row['suspend'] < 1);"));
$vis_act->addAction( $h_toggle->createAction("Enable", "&field=suspend&status=0", "return (\$row['suspend'] > 0);"));
$view->getColumn("status")->setCellRenderer($vis_act);


$ac = new AdminAccessBean();

function draw_access_level(&$row, TableColumn $tc)
{

  $key_id = $tc->getView()->getIterator()->getPrKey();
  $id = $row[$key_id];

 
  echo $row["access_level"]."<br>";

  if (strcmp($row["access_level"],"Limited Access")==0) {
	global $ac;
	$ac->startIterator("WHERE $key_id=$id");
	while ($ac->fetchNext($rowac)){

	  echo "<small>";
	  echo $rowac["role"];
	  echo "</small><br>";
	}
  }
	
}

$view->setCaption("Admin Users List");

$page->beginPage($menu);

echo "<div class='page_caption'>";
echo tr("Administrative Users");
echo "</div>";

$view->render();


$page->finishPage();


?>
