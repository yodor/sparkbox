<?php


include_once("BeanList.php");
include_once("components/NestedSetTreeView.php");
include_once("components/renderers/items/TextTreeItem.php");

class BeanTree extends BeanList
{

    public function __construct()
    {
        parent::__construct();
    }

    public function initItemActions(ActionCollection $act): void
    {
        $h_repos = new ChangePositionResponder($this->bean);

        $h_delete = new DeleteItemResponder($this->bean);
        $h_delete->setConfirmDialogText("<div class='alert'>" . tr("All related items will also be deleted.") . "</div>");

        $actionUp = $h_repos->createAction("Up");
        $actionUp->getURL()->add(new URLParameter("type", "left"));
        $actionUp->getURL()->add(new DataParameter("item_id", $this->bean->key()));

        $actionDown = $h_repos->createAction("Down");
        $actionDown->getURL()->add(new URLParameter("type", "right"));
        $actionDown->getURL()->add(new DataParameter("item_id", $this->bean->key()));

        $act->append($actionUp);
        $act->append($actionDown);

        $editAction = TemplateContent::CreateAction(SparkTemplateAdminPage::ACTION_EDIT, tr(SparkTemplateAdminPage::ACTION_EDIT));
        $editAction->getURL()->add(new DataParameter("editID", $this->bean->key()));
        $act->append($editAction);

        $act->append($h_delete->createAction());

    }

    public function initialize(): void
    {

        $tv = new NestedSetTreeView();

        $ir = new TextTreeItem();

        $tv->setItemRenderer($ir);

        $this->initItemActions($ir->getActions());

        $tv->setBranchRenderMode(NestedSetTreeView::MODE_BRANCHES_UNFOLDED);

        $tv->setName(get_class($this->bean));

        $tv->setIterator(new SelectQuery($this->bean->selectTree(array_keys($this->fields)), $this->bean->key(), $this->bean->getTableName()));
        $tv->getItemRenderer()->setLabelKey(array_keys($this->fields)[0]);

        $this->cmp = $tv;

    }


    public function treeView(): NestedSetTreeView
    {
        if ($this->cmp instanceof NestedSetTreeView) return $this->cmp;
        throw new Exception("Incorrect component class - expected NestedSetTreeView");
    }

    protected function getContentTitle(): string
    {
        return "Tree";
    }
}