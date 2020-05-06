<?php
include_once("components/Component.php");
include_once("components/renderers/ICellRenderer.php");
include_once("components/TableColumn.php");

class ColorCodeCellRenderer extends TableCellRenderer implements ICellRenderer
{

    public function renderCell(array &$row, TableColumn $tc)
    {
        $this->processAttributes($row, $tc);

        $this->startRender();
        $field_key = $tc->getFieldName();

        echo "<div class='color_value' style='background-color:{$row[$field_key]}'>";
        echo "</div>";


        $this->finishRender();
    }
}

?>
