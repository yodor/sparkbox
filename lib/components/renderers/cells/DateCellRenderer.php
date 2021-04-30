<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class DateCellRenderer extends TableCellRenderer
{

    protected $format = "j F Y\<\B\R\>H:i:s";

    public function __construct($format = "j F Y\<\B\R\>H:i:s")
    {
        parent::__construct();
        $this->format = $format;
    }

    public function setColumn(TableColumn $tc)
    {
        parent::setColumn($tc);
        $tc->setAlignClass(TableColumn::ALIGN_CENTER);
    }

    protected function renderImpl()
    {
        echo "<span>";

        if (strcmp($this->value, "0000-00-00") == 0 || strlen($this->value) < 1) {
            echo "N/A";
        }
        else {
            echo date($this->format, strtotime($this->value));
        }

        echo "<span>";
    }

}

?>