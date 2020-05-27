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

    public function requiredStyle()
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

    public function setData(array &$row)
    {
        parent::setData($row);

        $iterator = $this->actions->iterator();
        while ($iterator->valid()) {
            $action = $iterator->current();
            if ($action instanceof Action) {
                $action->setData($row);
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

    //    /**
    //     * Add default query parameter to all actions in this collection
    //     * @param URLParameter $param
    //     * @return void
    //     */
    //    public function addURLParameter(URLParameter $param)
    //    {
    //        // TODO: Implement addURLParameter() method.
    //        $this->urlparam = $param;
    //    }
}

?>