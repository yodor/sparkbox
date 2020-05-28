<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class NumericCellRenderer extends TableCellRenderer
{

    protected $format = "%01.2f";

    public function __construct(string $format = "")
    {
        parent::__construct();

        if ($format) {
            $this->format = $format;
        }
    }

    public function setColumn(TableColumn $tc)
    {
        parent::setColumn($tc);
        $tc->setAlignClass(TableColumn::ALIGN_RIGHT);
    }

    protected function renderImpl()
    {

        echo sprintf($this->format, $this->value);

    }

}

?>
