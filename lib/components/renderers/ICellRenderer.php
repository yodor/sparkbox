<?php
include_once("lib/components/TableColumn.php");

interface ICellRenderer
{
    public function renderCell($row, TableColumn $tc);

    public function setTooltipFromField($field_name);

}

?>