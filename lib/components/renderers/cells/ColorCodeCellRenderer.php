<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class ColorCodeCellRenderer extends TableCellRenderer
{

    protected string $color = "";

    protected function renderImpl()
    {
        echo "<div class='color_value' style='background-color:{$this->value}'>";
        echo "</div>";
    }

}

?>
