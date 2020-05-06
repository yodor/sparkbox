<?php
include_once("components/Component.php");
include_once("components/renderers/ICellRenderer.php");
include_once("components/TableColumn.php");

class DateFieldCellRenderer extends TableCellRenderer implements ICellRenderer
{

    protected $format;

    public function __construct($format = "j, F Y")
    {
        parent::__construct();

        $this->format = $format;
    }

    public function renderCell(array &$row, TableColumn $tc)
    {

        $this->processAttributes($row, $tc);

        $this->startRender();
        $field_key = $tc->getFieldName();

        $date_value = $row[$field_key];
        if (strcmp($date_value, "0000-00-00 00:00:00") == 0) {
            echo "N/A";
        }
        else {
            echo date($this->format, strtotime($date_value));

        }

        $this->finishRender();
    }
}

?>
