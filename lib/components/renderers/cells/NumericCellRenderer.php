<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class NumericCellRenderer extends TableCellRenderer
{

    protected $format;

    public function __construct(string $format = "%01.2f")
    {
        parent::__construct();

        $this->format = $format;

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