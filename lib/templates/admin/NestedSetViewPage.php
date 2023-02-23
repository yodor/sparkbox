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
        $ir->getActions()->append(new Action("Up", "?cmd=reposition&type=left", array(new DataParameter("item_id", $this->bean->key()))));
        $ir->getActions()->append(new Action("Down", "?cmd=reposition&type=right", array(new DataParameter("item_id", $this->bean->key()))));

        $ir->getActions()->append(new Action("Edit", "add.php", array(new DataParameter("editID", $this->bean->key()))));
        $ir->getActions()->append($h_delete->createAction());

        $tv = new NestedSetTreeView();
        $tv->setItemRenderer($ir);

        $tv->setName(get_class($this->bean));

        $tv->setIterator(new SQLQuery($this->bean->selectTree(array_keys($this->fields)), $this->bean->key(), $this->bean->getTableName()));
        $tv->getItemRenderer()->setLabelKey(array_keys($this->fields)[0]);

        $this->view = $tv;

        $this->append($this->view);

        $this->view_item_actions = $ir;

        return $this->view;
    }
}