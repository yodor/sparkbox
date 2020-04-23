<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/ICellRenderer.php");
include_once("lib/components/TableColumn.php");

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
