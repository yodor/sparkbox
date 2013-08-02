<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

include_once("lib/components/NestedSetTreeView.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");
include_once("class/beans/ProductCategoriesBean.php");

$page = new DemoPage();


$bean = new ProductCategoriesBean();

$ir = new TextTreeItemRenderer();
// $ir->addAction(new Action("Up", "?cmd=reposition&direction=left", array(new ActionParameter("item_id", $bean->getPrKey()))));
// $ir->addAction(new Action("Down", "?cmd=reposition&direction=right", array(new ActionParameter("item_id", $bean->getPrKey()))));


// $ir->addAction($h_delete->createAction());

$tv_item_clicked = new Action(
  "TextItemClicked", "tree.php?filter=self",
  array(
    new ActionParameter($bean->getPrKey(), $bean->getPrKey())
  )
);

$ir->setTextAction($tv_item_clicked);

$ir->addAction(
  new Action("Edit", "tree.php?filter=self", 
    array(
      new ActionParameter($bean->getPrKey(), $bean->getPrKey()),
      new ActionParameter("editID", $bean->getPrKey()),
    )
  )
);

$tv = new NestedSetTreeView();
$tv->setName("demo_tree");
$tv->open_all = false;
$tv->setSource($bean);
$tv->list_label = "category_name";



$tv->setItemRenderer($ir);


$tv->processFilters();

 
$page->beginPage();

$tv->render();

$page->finishPage();


?>