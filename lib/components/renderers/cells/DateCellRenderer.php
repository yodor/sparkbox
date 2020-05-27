<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class DateCellRenderer extends TableCellRenderer
{

    protected $format = "j, F Y";

    public function __construct($format = "j, F Y")
    {
        parent::__construct();
        $this->format = $format;
    }

    protected function renderImpl()
    {
        echo "<span>";
        if (strcmp($this->value, "0000-00-00 00:00:00") == 0) {
            echo "N/A";
        }
        else {
            echo date($this->format, strtotime($this->value));
        }
        echo "<span>";
    }

}

?>
