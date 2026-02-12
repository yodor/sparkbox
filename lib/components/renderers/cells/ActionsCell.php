<?php
include_once("components/renderers/cells/TableCell.php");
include_once("components/Action.php");
include_once("objects/ActionCollection.php");

class ActionsCell extends TableCell implements IActionCollection
{

    /**
     * @var ActionCollection
     */
    protected ActionCollection $actions;
    protected ClosureComponent $itemList;

    public function __construct()
    {
        parent::__construct();
        $this->actions = new ActionCollection();
        $this->itemList = new ClosureComponent(function(){
            Action::RenderActions($this->actions->toArray());
        });
        $this->itemList->setComponentClass("actions_list");
        $this->items()->append($this->itemList);

    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/Action.css";
        return $arr;
    }

    public function setActions(ActionCollection $actions): void
    {
        $this->actions = $actions;
    }

    public function getActions(): ?ActionCollection
    {
        return $this->actions;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->actions->setData($data);
        $this->setContents("");
    }

}