<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/ICellRenderer.php");
include_once("lib/components/TableColumn.php");

class NumericFieldCellRenderer extends TableCellRenderer implements ICellRenderer
{

    protected $format;

    public function __construct($format = "%01.2f")
    {
        parent::__construct();

        $this->format = $format;
    }

    public function renderCell($row, TableColumn $tc)
    {
        $this->processAttributes($row, $tc);

        $this->startRender();
        $field_key = $tc->getFieldName();

        echo sprintf($this->format, $row[$field_key]);

        $this->finishRender();
    }
}

?>
