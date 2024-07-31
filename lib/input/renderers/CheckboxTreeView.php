<?php
include_once ("input/renderers/InputField.php");
include_once ("components/renderers/items/TextTreeItem.php");
include_once ("components/NestedSetTreeView.php");

class CheckboxTreeView extends InputField
{
    protected NestedSetTreeView $treeView;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $ir = new TextTreeItem();
        $ir->enableCheckbox($input->getName());

        $this->treeView = new NestedSetTreeView();
        $this->treeView->setItemRenderer($ir);
        $this->treeView->setBranchRenderMode(NestedSetTreeView::MODE_BRANCHES_UNFOLDED);

        $this->setItemRenderer($ir);

    }

    public function setIterator(IDataIterator $query)
    {
        parent::setIterator($query);
        $this->treeView->setIterator($query);
    }

    public function renderImpl()
    {
        echo "<div class='FieldElements'>";
        $this->treeView->setCheckedNodes(...$this->input->getValue());
        $this->treeView->render();
        echo "</div>";

    }

}
