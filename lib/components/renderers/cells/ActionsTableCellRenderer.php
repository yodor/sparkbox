<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/Action.php");
include_once("components/renderers/IActionsCollection.php");

class ActionsTableCellRenderer extends TableCellRenderer implements IActionsCollection
{
    /**
     * @var array
     */
    protected $actions;

    protected $sortable = FALSE;

    public function __construct()
    {
        parent::__construct();
        $this->actions = array();

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getActions(): ?array
    {
        return $this->actions;
    }

    public function addAction(Action $a)
    {
        if ($a instanceof RowSeparator) {
            $this->actions["row_separator_" . count($this->actions)] = $a;
        }
        else if ($a instanceof PipeSeparator) {
            $this->actions["pipe_separator_" . count($this->actions)] = $a;
        }
        else {
            $this->actions[$a->getContents()] = $a;
        }
    }

    public function getAction(string $title): Action
    {
        return $this->actions[$title];
    }

    public function setData(array &$row)
    {
        parent::setData($row);

        $actions = array_keys($this->actions);
        foreach ($actions as $pos => $title) {
            $action = $this->getAction($title);
            $action->setData($row);
        }
    }

    protected function renderImpl()
    {
        echo "<div class='actions_list'>";

        $actions = array_keys($this->actions);
        foreach ($actions as $pos => $title) {
            $action = $this->getAction($title);
            $action->render();

        }

        echo "</div>";
    }

    /**
     * Add default query parameter to all actions in this collection
     * @param URLParameter $param
     * @return void
     */
    public function addURLParameter(URLParameter $param)
    {
        // TODO: Implement addURLParameter() method.
        $this->urlparam = $param;
    }
}

?>