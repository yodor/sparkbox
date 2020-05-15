<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/Action.php");

class LinkTableCellRenderer extends TableCellRenderer
{

    protected $action;

    public function __construct(Action $act)
    {
        parent::__construct();

        $this->action = $act;

    }

    protected function renderImpl()
    {
        $this->action->render();
    }

    public function setData(array &$row)
    {
        parent::setData($row);

        $this->action->setData($row);
        $this->action->setContents($this->value);


    }
}

?>
