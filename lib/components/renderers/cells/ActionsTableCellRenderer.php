<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("actions/Action.php");
include_once("components/renderers/ActionRenderer.php");
include_once("components/renderers/IActionsCollection.php");

class ActionsTableCellRenderer extends TableCellRenderer implements IActionsCollection
{
    /**
     * @var array
     */
    protected $actions;

    /**
     * @var ActionRenderer
     */
    protected $renderer;

    /**
     * Actions to render after eval'ing the checkCode
     * @var null
     */
    protected $render_actions = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->actions = array();
        $this->renderer = new ActionRenderer();
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "sparkfront/css/ActionRenderer.css";
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

    public function getAction(string $title): Action
    {
        return $this->actions[$title];
    }

    public function setColumn(TableColumn $parent)
    {
        parent::setColumn($parent);
        $parent->setSortable(FALSE);
    }

    public function setData(array &$row)
    {
        parent::setData($row);
        $this->renderer->setData($row);

        $this->render_actions = array();

        $actions = array_keys($this->actions);
        foreach ($actions as $pos => $title) {
            $action = $this->getAction($title);
            if (eval($action->getCheckCode())) {
                $this->render_actions[] = $action;
            }
        }
    }

    protected function renderImpl()
    {
        echo "<div class='actions_list'>";

        foreach ($this->render_actions as $pos => $action) {

            $this->renderer->setAction($action);
            $this->renderer->render();

        }

        echo "</div>";
    }
}

?>