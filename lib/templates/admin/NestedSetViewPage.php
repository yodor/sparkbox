<?php
include_once("templates/admin/BeanListPage.php");
include_once("components/NestedSetTreeView.php");
include_once("components/renderers/items/TextTreeItem.php");

class NestedSetViewPage extends BeanListPage
{

    public function __construct()
    {
        parent::__construct();
    }

    public function setBean(DBTableBean $bean)
    {
        if (!($bean instanceof NestedSetBean)) throw new Exception("Incorrect DBTableBean - Expecting NestedSetBean");
        parent::setBean($bean);
    }

    /**
     * Will use the first element from the $this->fields as a list label for the tree
     * @throws Exception
     */
    public function initView()
    {
        $h_repos = new ChangePositionResponder($this->bean);

        $h_delete = new DeleteItemResponder($this->bean);

        $ir = new TextTreeItem();

        $actionUp = $h_repos->createAction("Up");
        $actionUp->getURL()->add(new URLParameter("type", "left"));
        $actionUp->getURL()->add(new DataParameter("item_id",  $this->bean->key()));

        $actionDown = $h_repos->createAction("Down");
        $actionDown->getURL()->add(new URLParameter("type", "right"));
        $actionDown->getURL()->add(new DataParameter("item_id",  $this->bean->key()));

        $ir->getActions()->append($actionUp);
        $ir->getActions()->append($actionDown);

        $ir->getActions()->append(new Action("Edit", "add.php", array(new DataParameter("editID", $this->bean->key()))));
        $ir->getActions()->append($h_delete->createAction());

        $tv = new NestedSetTreeView();
        $tv->setItemRenderer($ir);
        $tv->setBranchRenderMode(NestedSetTreeView::MODE_BRANCHES_UNFOLDED);

        $tv->setName(get_class($this->bean));

        $tv->setIterator(new SQLQuery($this->bean->selectTree(array_keys($this->fields)), $this->bean->key(), $this->bean->getTableName()));
        $tv->getItemRenderer()->setLabelKey(array_keys($this->fields)[0]);

        $this->view = $tv;

        $this->items()->append($this->view);

        $this->view_item_actions = $ir->getActions();

        return $this->view;
    }
}
