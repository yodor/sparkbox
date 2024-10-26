<?php
include_once ("input/renderers/InputField.php");
include_once ("components/renderers/items/TextTreeItem.php");
include_once ("components/NestedSetTreeView.php");

class CheckboxTreeView extends DataIteratorField
{
    protected NestedSetTreeView $treeView;

    protected TextTreeItem $ir;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->ir = new TextTreeItem();

        $this->treeView = new NestedSetTreeView();
        $this->treeView->setItemRenderer($this->ir);
        $this->treeView->setBranchRenderMode(NestedSetTreeView::MODE_BRANCHES_UNFOLDED);

        $this->setItemRenderer($this->ir);

        $this->items()->removeAll($this->elements);

        $this->items()->append(new ClosureComponent($this->renderItems(...),false));
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->ir->enableCheckbox($this->dataInput->getName());
        $this->treeView->setCheckedNodes(...$this->dataInput->getValue());
    }

    public function setIterator(IDataIterator $query): void
    {
        parent::setIterator($query);
        $this->treeView->setIterator($query);
    }

    protected function renderItems() : void
    {
        $this->treeView->render();
    }

}
