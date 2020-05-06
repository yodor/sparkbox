<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/TableColumn.php");

class BooleanFieldCellRenderer extends TableCellRenderer implements ICellRenderer
{

    protected $true_value = "Enabled";
    protected $false_value = "Disabled";

    public function __construct($true_value = "Enabled", $false_value = "Disabled")
    {
        parent::__construct();

        $this->true_value = $true_value;
        $this->false_value = $false_value;
    }

    public function renderCell(array &$row, TableColumn $tc)
    {
        $this->processAttributes($row, $tc);
        $this->startRender();
        echo ($row[$tc->getFieldName()]) ? tr($this->true_value) : tr($this->false_value);
        $this->finishRender();
    }
}

?>