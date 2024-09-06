<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/Action.php");

class LinkCellRenderer extends TableCellRenderer
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
            $this->action->getURL()->fromString($this->value);
            $this->action->setContents($this->value);
            $this->action->render();
        }
    }

}

?>
