<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class LinkTableCellRenderer extends TableCellRenderer
{

    protected $renderer = NULL;

    public function __construct(Action $act)
    {
        parent::__construct();

        $this->renderer = new ActionRenderer($act);

    }

    protected function renderImpl()
    {
        $this->renderer->render();
    }

    public function setData(array &$row)
    {
        parent::setData($row);

        $this->renderer->getAction()->setTitle($this->value);
        $this->renderer->setData($row);

    }
}

?>
