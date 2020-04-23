<?php
include_once("lib/components/TableColumn.php");

interface ICellRenderer
{
    /**
     * @param array $row DataSource result row
     * @param TableColumn $tc
     * @return mixed
     */
    public function renderCell(array &$row, TableColumn $tc);

    /**
     * @param string $field_name
     * @return mixed
     */
    public function setTooltipFromField(string $field_name);

}

?>