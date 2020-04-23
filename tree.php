<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

include_once("lib/components/NestedSetTreeView2.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("lib/utils/NestedSetFilterProcessor.php");

$page = new DemoPage();


$bean = new ProductCategoriesBean();

$ir = new TextTreeItemRenderer();
// $ir->addAction(new Action("Up", "?cmd=reposition&direction=left", array(new ActionParameter("item_id", $bean->getPrKey()))));
// $ir->addAction(new Action("Down", "?cmd=reposition&direction=right", array(new ActionParameter("item_id", $bean->getPrKey()))));


$ir->addAction(new Action("Edit", "tree.php", array(//       new ActionParameter($bean->getPrKey(), $bean->getPrKey()),
                                                    new ActionParameter("editID", $bean->key()),)));

$tv = new NestedSetTreeView();
$tv->setName("demo_tree");
$tv->open_all = false;
$tv->setSource($bean);


$ir->setLabel("category_name");


$tv->setItemRenderer($ir);


$proc = new NestedSetFilterProcessor();
$proc->process($tv);


// $tv->processFilters();


$page->startRender();

$tv->render();

$page->finishRender();


?>