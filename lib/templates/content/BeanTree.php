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

        $beanClass = get_class($this->bean);
        if (!($this->bean instanceof NestedSetBean)) throw new Exception("Incorrect bean[$beanClass] - expecting NestedSetBean.");
        $view = new NestedSetTreeView();

        $ir = new TextTreeItem();

        $view->setItemRenderer($ir);

        $this->initItemActions($ir->getActions());

        $view->setBranchRenderMode(NestedSetTreeView::MODE_BRANCHES_UNFOLDED);

        $view->setName($beanClass);

        $select = $this->bean->selectTree(array_keys($this->fields));

        $query = new SelectQuery($select, $this->bean->key(), $this->bean->table());

        $view->setIterator($query);
        $view->getItemRenderer()->setLabelKey(array_keys($this->fields)[0]);

        $this->cmp = $view;

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