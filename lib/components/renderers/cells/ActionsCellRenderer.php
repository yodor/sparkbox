<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/Action.php");
include_once("utils/ActionCollection.php");

class ActionsCellRenderer extends TableCellRenderer implements IActionCollection
{
    /**
     * @var array
     */
    protected $actions;

    protected $sortable = FALSE;

    public function __construct()
    {
        parent::__construct();
        $this->actions = new ActionCollection();

    }

    public function setColumn(TableColumn $tc)
    {
        parent::setColumn($tc);
        $tc->setAlignClass(TableColumn::ALIGN_CENTER);
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function setActions(ActionCollection $actions)
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

        $iterator = $this->actions->iterator();
        while ($iterator->valid()) {
            $action = $iterator->current();
            if ($action instanceof Action) {
                $action->setData($data);
            }
            $iterator->next();
        }

    }

    protected function renderImpl()
    {
        echo "<div class='actions_list'>";

        $iterator = $this->actions->iterator();
        while ($iterator->valid()) {
            $action = $iterator->current();
            if ($action instanceof Action) {
                $action->render();
            }
            $iterator->next();
        }

        echo "</div>";
    }

}

?>
