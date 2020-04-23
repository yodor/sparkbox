<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/cells/TableCellRenderer.php");
include_once("lib/components/TableColumn.php");
include_once("lib/actions/Action.php");
include_once("lib/components/renderers/IActionsRenderer.php");
include_once("lib/components/renderers/ICellRenderer.php");
include_once("lib/components/renderers/ActionRenderer.php");

class ActionsTableCellRenderer extends TableCellRenderer implements ICellRenderer, IActionsRenderer
{
    protected $actions;


    public function __construct()
    {
        parent::__construct();
        $this->actions = array();


    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/ActionRenderer.css";
        return $arr;
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function addAction(Action $a)
    {
        if ($a instanceof RowSeparatorAction) {
            $this->actions["row_separator_" . count($this->actions)] = $a;
        }
        else if ($a instanceof PipeSeparatorAction) {
            $this->actions["pipe_separator_" . count($this->actions)] = $a;
        }
        else {
            $this->actions[$a->getTitle()] = $a;
        }
    }

    public function getAction($title)
    {
        return $this->actions[$title];
    }

    public function setParentComponent(Component $parent)
    {
        // 	  $tc = (TableColumn)$parent;
        $parent->getHeaderCellRenderer()->setSortable(false);
    }

    public function renderCell($row, TableColumn $tc)
    {
        $this->processAttributes($row, $tc);

        $this->startRender();

        $this->renderActions($row);

        $this->finishRender();
    }

    public function renderActions(array &$row)
    {
        echo "<div class='actions_list'>";

        $last_action = NULL;

        foreach ($this->actions as $key => $action) {
            if (eval($action->getCheckCode())) {

                $action = new ActionRenderer($action, $row);
                $action->render();
                $last_action = $action;

            }
        }

        echo "</div>";
    }
}

?>