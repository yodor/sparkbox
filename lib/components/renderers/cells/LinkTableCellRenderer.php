<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/ICellRenderer.php");
include_once("lib/components/TableColumn.php");

class LinkTableCellRenderer extends TableCellRenderer implements ICellRenderer
{

    protected $action = NULL;


    public function __construct(Action $act)
    {
        parent::__construct();

        $this->action = $act;

    }

    public function renderCell($row, TableColumn $tc)
    {
        $this->processAttributes($row, $tc);

        $this->startRender();
        $field_key = $tc->getFieldName();

        $this->action->setTitle($row[$field_key]);

        $ar = new ActionRenderer($this->action, $row);
        $ar->render();


        $this->finishRender();
    }
}

?>
