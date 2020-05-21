<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/Action.php");

class LinkTableCellRenderer extends TableCellRenderer
{

    protected $action;

    public function __construct()
    {
        parent::__construct();

        $this->action = new Action();

    }

    protected function renderImpl()
    {
        if ($this->value) {
            $this->action->getURLBuilder()->buildFrom($this->value);
            $this->action->setContents($this->value);
            $this->action->render();
        }
    }

}

?>
